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

            // Generate timestamp-based batch ID
            $timestamp = now()->format('YmdHis'); // Format: 20251110143022
            session(['checkout_batch_id' => $timestamp]);

            $data = [
                'assets' => $assets,
                'target' => $target,
                'admin' => $admin,
                'note' => $note,
                'type' => $type,
                'date' => now()->format('Y-m-d H:i:s'),
                'batch_id' => session('checkout_batch_id', $timestamp),
            ];

            $pdf = Pdf::loadView('pdf.checkout-summary', $data);
            
            // Generate filename
            $filename = "{$type}_{$timestamp}.pdf";
            $directory = public_path('uploads/checkouts');
            
            // Ensure directory exists
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            $fullPath = $directory . '/' . $filename;
            
            // Save PDF directly using file_put_contents
            $pdfOutput = $pdf->output();
            $bytesWritten = file_put_contents($fullPath, $pdfOutput);
            
            if ($bytesWritten === false) {
                throw new \Exception("Failed to write PDF to disk");
            }
            
            Log::info("PDF generated successfully", [
                'path' => $fullPath,
                'bytes' => $bytesWritten,
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
