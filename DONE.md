# PDF Checkout/Checkin Summary

## Descripción
Genera automáticamente PDFs resumen para operaciones de checkout y checkin de activos en Snipe-IT.

## Funcionamiento
1. **Checkout/Checkin individual**: Se genera 1 PDF con 1 activo
2. **Checkout masivo**: Se genera 1 PDF con todos los activos del lote (en lugar de múltiples PDFs)
3. Los PDFs se guardan en `public/uploads/checkouts/`
4. La generación ocurre automáticamente antes de enviar notificaciones por email

## Archivos Modificados
- `app/Services/PdfCheckoutService.php` - Servicio de generación
- `resources/views/pdf/checkout-summary.blade.php` - Template HTML
- `app/Listeners/CheckoutableListener.php` - Escucha eventos y genera PDFs
- `app/Http/Controllers/Assets/BulkAssetsController.php` - Tracking de lotes

## Lógica de Batch
- El controlador guarda `batch_id` y `asset_ids` en sesión
- El listener cuenta cuántos assets han sido procesados
- Cuando todos están listos, genera un solo PDF con todos
- Para checkouts individuales, genera inmediatamente

## Información en el PDF
- Asset tag, categoría, modelo, serial, estado, ubicación
- Usuario/ubicación destino
- Administrador que realiza la operación
- Fecha/hora, notas, y Batch ID
