<?php

namespace App\Http\Controllers;

use App\Models\AssetModel;
use App\Models\Company;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ApartadoEquipoController extends Controller
{
    /**
     * Display the equipment reservation form
     */
    public function index()
    {
        $this->authorize('view', Asset::class);
        
        $companies = Company::orderBy('name', 'asc')->get();
        
        return view('apartado.index', compact('companies'));
    }

    /**
     * Search models by company
     */
    public function searchModels(Request $request)
    {
        $this->authorize('view', Asset::class);
        
        $companyId = $request->input('company_id');
        $search = $request->input('search', '');
        
        Log::info('Searching models', ['company_id' => $companyId, 'search' => $search]);
        
        // Get all models that have at least one available asset in the specified company
        $models = AssetModel::whereHas('assets', function($query) use ($companyId) {
            $query->where('company_id', $companyId)
                  ->whereNull('assigned_to')
                  ->whereNull('deleted_at');
        })
        ->where(function($query) use ($search) {
            if ($search) {
                $query->where('name', 'LIKE', '%' . $search . '%')
                      ->orWhere('model_number', 'LIKE', '%' . $search . '%');
            }
        })
        ->withCount(['assets as available_count' => function($query) use ($companyId) {
            $query->where('company_id', $companyId)
                  ->whereNull('assigned_to')
                  ->whereNull('deleted_at');
        }])
        ->having('available_count', '>', 0)
        ->orderBy('name', 'asc')
        ->limit(50)
        ->get();
        
        Log::info('Found models', ['count' => $models->count()]);
        
        return response()->json($models);
    }

    /**
     * Get available quantity for a specific model in a company
     */
    public function getModelQuantity(Request $request)
    {
        $this->authorize('view', Asset::class);
        
        $companyId = $request->input('company_id');
        $modelId = $request->input('model_id');
        
        $availableCount = Asset::where('model_id', $modelId)
            ->where('company_id', $companyId)
            ->whereNull('assigned_to')
            ->whereNull('deleted_at')
            ->count();
        
        return response()->json(['available_count' => $availableCount]);
    }

    /**
     * Submit the reservation request and send email
     */
    public function submit(Request $request)
    {
        try {
            $this->authorize('view', Asset::class);
            
            Log::info('Apartado submission received', ['data' => $request->all()]);
            
            // Simplified validation - don't check if model_id exists in database
            // We'll validate it manually
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                //'professo1r_name' => 'required|string|max:255',
                'workshop_name' => 'required|string|max:255',
                'needed_date' => 'required|date|after_or_equal:today',
                'equipment_list' => 'required|array|min:1',
                'equipment_list.*.model_id' => 'required|integer',
                'equipment_list.*.quantity' => 'required|integer|min:1',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                Log::warning('Apartado validation failed', ['errors' => $validator->errors()]);
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $company = Company::find($request->company_id);
            $equipmentDetails = [];
            
            // Manually validate that models exist
            foreach ($request->equipment_list as $item) {
                $model = AssetModel::find($item['model_id']);
                
                if (!$model) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Modelo de equipo no encontrado (ID: ' . $item['model_id'] . ')'
                    ], 422);
                }
                
                $available = Asset::where('model_id', $item['model_id'])
                    ->where('company_id', $request->company_id)
                    ->whereNull('assigned_to')
                    ->whereNull('deleted_at')
                    ->count();
                
                $equipmentDetails[] = [
                    'model' => $model,
                    'quantity' => $item['quantity'],
                    'available' => $available
                ];
            }

            // Get user name safely
            $user = auth()->user();
            $userName = '';
            if ($user) {
                // Try different methods to get the name
                if (method_exists($user, 'getFullNameAttribute')) {
                    $userName = $user->getFullNameAttribute();
                } elseif (isset($user->first_name) && isset($user->last_name)) {
                    $userName = $user->first_name . ' ' . $user->last_name;
                } elseif (isset($user->name)) {
                    $userName = $user->name;
                } else {
                    $userName = $user->username ?? 'Usuario';
                }
            }
            
            $reservationData = [
                'company' => $company,
                //'professo1r_name' => $request->professo1r_name,
                'workshop_name' => $request->workshop_name,
                'needed_date' => $request->needed_date,
                'equipment' => $equipmentDetails,
                'notes' => $request->notes,
                'submitted_by' => $userName,
                'submitted_at' => now()->format('Y-m-d H:i:s')
            ];

            Log::info('Reservation data prepared', ['reservation' => $reservationData]);

            // Send email to lab assistant
            try {
                $this->sendReservationEmail($reservationData);
                Log::info('Apartado email sent successfully');
            } catch (\Exception $e) {
                Log::error('Failed to send apartado email: ' . $e->getMessage());
                // Continue even if email fails - don't block the submission
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Solicitud de apartado enviada exitosamente al asistente del laboratorio.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Apartado submission error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send reservation email to lab assistant
     */
    private function sendReservationEmail($data)
    {
        try {
            $settings = \App\Models\Setting::getSettings();
            $labAssistantEmail = $settings->admin_email ?? null;
            
            // If no admin email, try alert email
            if (empty($labAssistantEmail) && isset($settings->alert_email)) {
                $labAssistantEmail = $settings->alert_email;
            }
            
            // If still no email, use a default or skip
            if (empty($labAssistantEmail)) {
                Log::warning('No admin email configured for apartado notifications');
                Log::info('Apartado data (email not sent)', ['data' => $data]);
                
                // TEMPORARY: You can hardcode an email here for testing
                $labAssistantEmail = 'electrolaimi@gmail.com';
                // Uncomment the line above and add your email for testing
                
                return; // Skip sending email
            }
            
            Mail::send('emails.apartado-equipo', ['data' => $data], function ($message) use ($labAssistantEmail, $data) {
                $message->to($labAssistantEmail)
                        ->subject('Nueva Solicitud de Apartado de Equipo - ' . $data['workshop_name']);
            });
            
            Log::info('Apartado email sent to: ' . $labAssistantEmail);
        } catch (\Exception $e) {
            Log::error('Error sending apartado email: ' . $e->getMessage());
        }
    }
}
