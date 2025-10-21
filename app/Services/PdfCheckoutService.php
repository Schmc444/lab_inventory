<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PdfCheckoutService
{
    /**
     * Generate a PDF for checkout operation
     * 
     * @param Collection $assets
     * @param mixed $target
     * @param mixed $admin
     * @param string|null $note
     * @param string $type 'checkout' or 'checkin'
     * @return string|null Path to saved PDF
     */
    public function generateCheckoutPdf(Collection $assets, $target, $admin, ?string $note, string $type = 'checkout'): ?string
    {
        try {
            // Load relationships for each asset individually
            $assets->each(function ($asset) {
                if (method_exists($asset, 'load')) {
                    $asset->load(['model.category', 'assetstatus', 'assignedTo', 'location']);
                }
            });

            $data = [
                'assets' => $assets,
                'target' => $target,
                'admin' => $admin,
                'note' => $note,
                'type' => $type,
                'date' => now()->format('Y-m-d H:i:s'),
                'batch_id' => session('checkout_batch_id', uniqid()),
            ];

            $pdf = Pdf::loadView('pdf.checkout-summary', $data);
            
            // Generate filename
            $timestamp = now()->format('Ymd_His');
            $filename = "{$type}_{$timestamp}_" . $data['batch_id'] . ".pdf";
            $directory = 'checkouts';
            
            // Use public disk (which points to public/uploads in Snipe-IT)
            $disk = Storage::disk('public');
            
            // Ensure directory exists
            if (!$disk->exists($directory)) {
                $disk->makeDirectory($directory);
            }
            
            $path = "{$directory}/{$filename}";
            $fullPath = public_path("uploads/{$path}");
            
            // Save PDF
            $disk->put($path, $pdf->output());
            
            Log::info("PDF generated successfully", [
                'path' => $fullPath,
                'assets_count' => $assets->count(),
                'type' => $type
            ]);
            
            return $fullPath;
            
        } catch (\Exception $e) {
            Log::error("Failed to generate PDF: " . $e->getMessage(), [
                'exception' => $e,
                'assets_count' => $assets->count() ?? 0
            ]);
            return null;
        }
    }
}
