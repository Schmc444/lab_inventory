<?php

namespace App\Listeners;

use App\Events\CheckoutableCheckedOut;
use App\Mail\CheckinAccessoryMail;
use App\Mail\CheckinComponentMail;
use App\Mail\CheckinLicenseMail;
use App\Mail\CheckoutAccessoryMail;
use App\Mail\CheckoutAssetMail;
use App\Mail\CheckinAssetMail;
use App\Mail\CheckoutComponentMail;
use App\Mail\CheckoutConsumableMail;
use App\Mail\CheckoutLicenseMail;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Category;
use App\Models\CheckoutAcceptance;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\LicenseSeat;
use App\Models\Location;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\CheckinAccessoryNotification;
use App\Notifications\CheckinAssetNotification;
use App\Notifications\CheckinComponentNotification;
use App\Notifications\CheckinLicenseSeatNotification;
use App\Notifications\CheckoutAccessoryNotification;
use App\Notifications\CheckoutAssetNotification;
use App\Notifications\CheckoutComponentNotification;
use App\Notifications\CheckoutConsumableNotification;
use App\Notifications\CheckoutLicenseSeatNotification;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Osama\LaravelTeamsNotification\TeamsNotification;
use App\Services\PdfCheckoutService;

class CheckoutableListener
{
    private array $skipNotificationsFor = [
//        Component::class,
    ];

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            \App\Events\CheckoutableCheckedIn::class,
            'App\Listeners\CheckoutableListener@onCheckedIn'
        );

        $events->listen(
            \App\Events\CheckoutableCheckedOut::class,
            'App\Listeners\CheckoutableListener@onCheckedOut'
        );
    }

    /**
     * Notify the user and post to webhook about the checked out checkoutable
     * and add a record to the checkout_requests table.
     */
    public function onCheckedOut($event)
    {
        // Generate PDF first, before any other logic
        $this->handlePdfGeneration($event, 'checkout');

        if ($this->shouldNotSendAnyNotifications($event->checkoutable)) {
            return;
        }

        $acceptance = $this->getCheckoutAcceptance($event);

        $shouldSendEmailToUser = $this->shouldSendCheckoutEmailToUser($event->checkoutable);
        $shouldSendEmailToAlertAddress = $this->shouldSendEmailToAlertAddress($acceptance);
        $shouldSendWebhookNotification = $this->shouldSendWebhookNotification();

        if (!$shouldSendEmailToUser && !$shouldSendEmailToAlertAddress && !$shouldSendWebhookNotification) {
            return;
        }

        // Handle bulk email notifications
        if ($shouldSendEmailToUser || $shouldSendEmailToAlertAddress) {
            $this->handleBulkEmailNotification($event, 'checkout', $shouldSendEmailToUser, $shouldSendEmailToAlertAddress, $acceptance);
        }

        if ($shouldSendWebhookNotification) {
            try {
                if ($this->newMicrosoftTeamsWebhookEnabled()) {
                    $message = $this->getCheckoutNotification($event)->toMicrosoftTeams();
                    $notification = new TeamsNotification(Setting::getSettings()->webhook_endpoint);
                    $notification->success()->sendMessage($message[0], $message[1]);  // Send the message to Microsoft Teams
                } else {
                    Notification::route($this->webhookSelected(), Setting::getSettings()->webhook_endpoint)
                        ->notify($this->getCheckoutNotification($event, $acceptance));
                }
            } catch (ClientException $e) {
                if (strpos($e->getMessage(), 'channel_not_found') !== false) {
                    Log::warning(Setting::getSettings()->webhook_selected . " notification failed: " . $e->getMessage());
                } else {
                    Log::error("ClientException caught during checkout notification: " . $e->getMessage());
                }
            } catch (Exception $e) {
                Log::warning(ucfirst(Setting::getSettings()->webhook_selected) . ' webhook notification failed:', [
                    'error' => $e->getMessage(),
                    'webhook_endpoint' => Setting::getSettings()->webhook_endpoint,
                    'event' => $event,
                ]);
            }
        }
    }

    /**
     * Notify the user and post to webhook about the checked in checkoutable
     */
    public function onCheckedIn($event)
    {
        // FORCE log to file
        file_put_contents(storage_path('logs/DEBUG_CHECKIN.txt'), 
            date('Y-m-d H:i:s') . " - onCheckedIn called for asset: " . ($event->checkoutable->id ?? 'N/A') . 
            " | batch_id: " . session('checkin_batch_id', 'NO SESSION') . "\n", 
            FILE_APPEND
        );

        // Generate PDF FIRST for Assets (even before notification checks)
        if ($event->checkoutable instanceof Asset) {
            $this->handlePdfGeneration($event, 'checkin');
        }

        $shouldNotSend = $this->shouldNotSendAnyNotifications($event->checkoutable);
        file_put_contents(storage_path('logs/DEBUG_CHECKIN.txt'), 
            date('Y-m-d H:i:s') . " - shouldNotSendAnyNotifications: " . ($shouldNotSend ? 'TRUE (RETURNING)' : 'FALSE') . "\n", 
            FILE_APPEND
        );
        
        if ($shouldNotSend) {
            return;
        }

        $shouldSendEmailToUser = $this->checkoutableCategoryShouldSendEmail($event->checkoutable);
        $shouldSendEmailToAlertAddress = $this->shouldSendEmailToAlertAddress();
        $shouldSendWebhookNotification = $this->shouldSendWebhookNotification();
        
        file_put_contents(storage_path('logs/DEBUG_CHECKIN.txt'), 
            date('Y-m-d H:i:s') . " - Email flags: user=" . ($shouldSendEmailToUser ? 'YES' : 'NO') . 
            " | alert=" . ($shouldSendEmailToAlertAddress ? 'YES' : 'NO') . 
            " | webhook=" . ($shouldSendWebhookNotification ? 'YES' : 'NO') . "\n", 
            FILE_APPEND
        );
        
        if (!$shouldSendEmailToUser && !$shouldSendEmailToAlertAddress && !$shouldSendWebhookNotification) {
            return;
        }

        if ($shouldSendEmailToUser || $shouldSendEmailToAlertAddress) {
            /**
             * Send the appropriate notification
             */
            if ($event->checkedOutTo && $event->checkoutable) {
                $acceptances = CheckoutAcceptance::where('checkoutable_id', $event->checkoutable->id)
                    ->where('assigned_to_id', $event->checkedOutTo->id)
                    ->get();

                foreach ($acceptances as $acceptance) {
                    if ($acceptance->isPending()) {
                        $acceptance->delete();
                    }
                }
            }

            // Handle bulk email notifications for checkin
            file_put_contents(storage_path('logs/DEBUG_CHECKIN.txt'), 
                date('Y-m-d H:i:s') . " - ABOUT TO CALL handleBulkEmailNotification\n", 
                FILE_APPEND
            );
            
            $this->handleBulkEmailNotification($event, 'checkin', $shouldSendEmailToUser, $shouldSendEmailToAlertAddress);
            
            file_put_contents(storage_path('logs/DEBUG_CHECKIN.txt'), 
                date('Y-m-d H:i:s') . " - RETURNED FROM handleBulkEmailNotification\n", 
                FILE_APPEND
            );
        }

        if ($shouldSendWebhookNotification) {
            // Send Webhook notification
            try {
                if ($this->newMicrosoftTeamsWebhookEnabled()) {
                    $message = $this->getCheckinNotification($event)->toMicrosoftTeams();
                    $notification = new TeamsNotification(Setting::getSettings()->webhook_endpoint);
                    $notification->success()->sendMessage($message[0], $message[1]); // Send the message to Microsoft Teams
                } else {
                    Notification::route($this->webhookSelected(), Setting::getSettings()->webhook_endpoint)
                        ->notify($this->getCheckinNotification($event));
                }
            } catch (ClientException $e) {
                if (strpos($e->getMessage(), 'channel_not_found') !== false) {
                    Log::warning(Setting::getSettings()->webhook_selected . " notification failed: " . $e->getMessage());
                } else {
                    Log::error("ClientException caught during checkin notification: " . $e->getMessage());
                }
            } catch (Exception $e) {
                Log::warning(ucfirst(Setting::getSettings()->webhook_selected) . ' webhook notification failed:', [
                    'error' => $e->getMessage(),
                    'webhook_endpoint' => Setting::getSettings()->webhook_endpoint,
                    'event' => $event,
                ]);
            }
        }
    }

    /**
     * Handle PDF generation for checkout/checkin operations
     */
    private function handlePdfGeneration($event, string $type)
    {
        // Only generate PDF for Assets
        if (!($event->checkoutable instanceof Asset)) {
            return;
        }

        // Check for both checkout and checkin batch IDs
        $batchId = session('checkout_batch_id') ?? session('checkin_batch_id');
        $batchAssetIds = session('checkout_batch_assets', []) ?: session('checkin_batch_assets', []);
        
        // Initialize batch tracking if not exists
        if (!session()->has('pdf_batch_processed')) {
            session()->put('pdf_batch_processed', []);
        }
        
        $processedBatches = session('pdf_batch_processed', []);
        
        // Check if this batch was already processed
        if ($batchId && in_array($batchId, $processedBatches)) {
            return;
        }

        // For bulk operations (checkout or checkin)
        if ($batchId && !empty($batchAssetIds)) {
            $currentAssetIds = session('pdf_current_assets', []);
            $currentAssetIds[] = $event->checkoutable->id;
            session()->put('pdf_current_assets', $currentAssetIds);
            
            // Check if all assets from the batch have been processed
            if (count($currentAssetIds) >= count($batchAssetIds)) {
                $assets = Asset::whereIn('id', $batchAssetIds)->get();
                
                // Get target and admin based on operation type
                if ($type === 'checkout') {
                    $target = session('checkout_batch_target');
                    $admin = session('checkout_batch_admin');
                    $note = session('checkout_batch_note');
                } else {
                    // For checkin, target is null
                    $target = null;
                    $admin = session('checkin_batch_admin');
                    $note = session('checkin_batch_note');
                }
                
                $pdfService = app(PdfCheckoutService::class);
                $pdfPath = $pdfService->generateCheckoutPdf($assets, $target, $admin, $note, $type);
                
                if ($pdfPath) {
                    Log::info("Batch PDF generated", ['path' => $pdfPath, 'batch_id' => $batchId, 'type' => $type]);
                }
                
                // Mark batch as processed
                $processedBatches[] = $batchId;
                session()->put('pdf_batch_processed', $processedBatches);
                
                // IMPORTANT: Don't clean up session here!
                // Let handleBulkEmailNotification clean up after sending emails
                // Only clean up PDF-specific data
                session()->forget(['pdf_current_assets']);
            }
        } else {
            // For individual checkout/checkin
            // Load relationships before creating collection
            if (method_exists($event->checkoutable, 'load')) {
                $event->checkoutable->load(['model.category', 'assetstatus', 'assignedTo', 'location']);
            }
            
            $assets = collect([$event->checkoutable]);
            $target = $event->checkedOutTo ?? null;
            $admin = $type === 'checkout' ? $event->checkedOutBy : $event->checkedInBy;
            $note = $event->note ?? null;
            
            $pdfService = app(PdfCheckoutService::class);
            $pdfPath = $pdfService->generateCheckoutPdf($assets, $target, $admin, $note, $type);
            
            if ($pdfPath) {
                Log::info("Individual PDF generated", ['path' => $pdfPath, 'asset_id' => $event->checkoutable->id]);
            }
        }
    }

    /**
     * Generates a checkout acceptance
     * @param  Event $event
     * @return mixed
     */
    private function getCheckoutAcceptance($event)
    {
        $checkedOutToType = get_class($event->checkedOutTo);
        if ($checkedOutToType != "App\Models\User") {
            return null;
        }

        if (!$event->checkoutable->requireAcceptance()) {
            return null;
        }

        $acceptance = new CheckoutAcceptance;
        $acceptance->checkoutable()->associate($event->checkoutable);
        $acceptance->assignedTo()->associate($event->checkedOutTo);

        $acceptance->qty = 1;

        if (isset($event->checkoutable->checkout_qty)) {
            $acceptance->qty = $event->checkoutable->checkout_qty;
        }

        $category = $this->getCategoryFromCheckoutable($event->checkoutable);

        if ($category?->alert_on_response) {
            $acceptance->alert_on_response_id = auth()->id();
        }
        
        $acceptance->save();

        return $acceptance;
    }

    /**
     * Get the appropriate notification for the event
     *
     * @param  CheckoutableCheckedIn  $event
     * @return Notification
     */
    private function getCheckinNotification($event)
    {

        $notificationClass = null;

        switch (get_class($event->checkoutable)) {
            case Accessory::class:
                $notificationClass = CheckinAccessoryNotification::class;
                break;
            case Asset::class:
                $notificationClass = CheckinAssetNotification::class;
                break;
            case LicenseSeat::class:
                $notificationClass = CheckinLicenseSeatNotification::class;
                break;
            case Component::class:
                $notificationClass = CheckinComponentNotification::class;
                break;
        }

        Log::debug('Notification class: '.$notificationClass);

        return new $notificationClass($event->checkoutable, $event->checkedOutTo, $event->checkedInBy, $event->note);
    }
    /**
     * Get the appropriate notification for the event
     * 
     * @param  CheckoutableCheckedOut $event
     * @param  CheckoutAcceptance|null $acceptance
     * @return Notification
     */
    private function getCheckoutNotification($event, $acceptance = null)
    {
        $notificationClass = null;

        switch (get_class($event->checkoutable)) {
            case Accessory::class:
                $notificationClass = CheckoutAccessoryNotification::class;
                break;
            case Asset::class:
                $notificationClass = CheckoutAssetNotification::class;
                break;
            case Consumable::class:
                $notificationClass = CheckoutConsumableNotification::class;
                break;
            case LicenseSeat::class:
                $notificationClass = CheckoutLicenseSeatNotification::class;
                break;
            case Component::class:
                $notificationClass = CheckoutComponentNotification::class;
            break;
        }


        return new $notificationClass($event->checkoutable, $event->checkedOutTo, $event->checkedOutBy, $acceptance, $event->note);
    }
    private function getCheckoutMailType($event, $acceptance){
        $lookup = [
            Accessory::class => CheckoutAccessoryMail::class,
            Asset::class => CheckoutAssetMail::class,
            LicenseSeat::class => CheckoutLicenseMail::class,
            Consumable::class => CheckoutConsumableMail::class,
            Component::class => CheckoutComponentMail::class,
        ];
        $mailable= $lookup[get_class($event->checkoutable)];

        return new $mailable($event->checkoutable, $event->checkedOutTo, $event->checkedOutBy, $acceptance, $event->note);

    }

    private function getCheckinMailType($event){
        $lookup = [
            Accessory::class => CheckinAccessoryMail::class,
            Asset::class => CheckinAssetMail::class,
            LicenseSeat::class => CheckinLicenseMail::class,
            Component::class => CheckinComponentMail::class,
        ];
        $mailable= $lookup[get_class($event->checkoutable)];

        return new $mailable($event->checkoutable, $event->checkedOutTo, $event->checkedInBy, $event->note);

    }

    /**
     * This gets the recipient objects based on the type of checkoutable.
     * The 'name' property for users is set in the boot method in the User model.
     *
     * @see \App\Models\User::boot()
     * @param $event
     * @return mixed
     */
    private function getNotifiableUser($event)
    {

        // If it's assigned to an asset, get that asset's assignedTo object
        if ($event->checkedOutTo instanceof Asset){
            $event->checkedOutTo->load('assignedTo');
            return $event->checkedOutTo->assignedto;

        // If it's assigned to a location, get that location's manager object
        } elseif ($event->checkedOutTo instanceof Location) {
            return $event->checkedOutTo->manager;

        // Otherwise just return the assigned to object
        } else {
            return $event->checkedOutTo;
        }
    }

    private function webhookSelected(){
        if(Setting::getSettings()->webhook_selected === 'slack' || Setting::getSettings()->webhook_selected === 'general'){
            return 'slack';
        }

        return Setting::getSettings()->webhook_selected;
    }

    private function shouldNotSendAnyNotifications($checkoutable): bool
    {
        return in_array(get_class($checkoutable), $this->skipNotificationsFor);
    }

    private function shouldSendWebhookNotification(): bool
    {
        return Setting::getSettings() && Setting::getSettings()->webhook_endpoint;
    }

    private function checkoutableCategoryShouldSendEmail(Model $checkoutable): bool
    {
        if ($checkoutable instanceof LicenseSeat) {
            return $checkoutable->license->checkin_email();
        }
        return (method_exists($checkoutable, 'checkin_email') && $checkoutable->checkin_email());
    }

    private function newMicrosoftTeamsWebhookEnabled(): bool
    {
        return Setting::getSettings()->webhook_selected === 'microsoft' && Str::contains(Setting::getSettings()->webhook_endpoint, 'workflows');
    }

    private function shouldSendCheckoutEmailToUser(Model $checkoutable): bool
    {
        /**
         * Send an email if any of the following conditions are met:
         * 1. The asset requires acceptance
         * 2. The item has a EULA
         * 3. The item should send an email at check-in/check-out
         */

        if ($checkoutable->requireAcceptance()) {
            return true;
        }

        if ($checkoutable->getEula()) {
            return true;
        }

        if ($this->checkoutableCategoryShouldSendEmail($checkoutable)) {
            return true;
        }

        return false;
    }

    private function shouldSendEmailToAlertAddress($acceptance = null): bool
    {
        $setting = Setting::getSettings();

        if (!$setting) {
            return false;
        }

        if (is_null($acceptance) && !$setting->admin_cc_always) {
            return false;
        }

        return (bool) $setting->admin_cc_email;
    }

    private function getFormattedAlertAddresses(): array
    {
        $alertAddresses = Setting::getSettings()->admin_cc_email;

        if ($alertAddresses !== '') {
            return array_filter(array_map('trim', explode(',', $alertAddresses)));
        }

        return [];
    }

    private function generateEmailRecipients(
        bool $shouldSendEmailToUser,
        bool $shouldSendEmailToAlertAddress,
        mixed $notifiable
    ): array {
        $to = [];
        $cc = [];

        // if user && cc: to user, cc admin
        if ($shouldSendEmailToUser && $shouldSendEmailToAlertAddress) {
            $to[] = $notifiable;
            $cc[] = $this->getFormattedAlertAddresses();
        }

        // if user && no cc: to user
        if ($shouldSendEmailToUser && !$shouldSendEmailToAlertAddress) {
            $to[] = $notifiable;
        }

        // if no user && cc: to admin
        if (!$shouldSendEmailToUser && $shouldSendEmailToAlertAddress) {
            $to[] = $this->getFormattedAlertAddresses();
        }

        return array($to, $cc);
    }

    private function getCategoryFromCheckoutable(Model $checkoutable): ?Category
    {
        return match (true) {
            $checkoutable instanceof Asset => $checkoutable->model->category,
            $checkoutable instanceof Accessory,
                $checkoutable instanceof Consumable,
                $checkoutable instanceof Component => $checkoutable->category,
            $checkoutable instanceof LicenseSeat => $checkoutable->license->category,
        };
    }

    /**
     * Handle bulk email notifications for checkout/checkin operations
     */
    private function handleBulkEmailNotification($event, string $type, bool $shouldSendEmailToUser, bool $shouldSendEmailToAlertAddress, $acceptance = null)
    {
        file_put_contents(storage_path('logs/DEBUG_CHECKIN.txt'), 
            date('Y-m-d H:i:s') . " - INSIDE handleBulkEmailNotification for asset " . $event->checkoutable->id . "\n", 
            FILE_APPEND
        );

        // Only process for Assets
        if (!($event->checkoutable instanceof Asset)) {
            file_put_contents(storage_path('logs/DEBUG_CHECKIN.txt'), 
                date('Y-m-d H:i:s') . " - Not an Asset, returning\n", 
                FILE_APPEND
            );
            return;
        }

        // Check for both checkout and checkin batch IDs
        $batchId = session('checkout_batch_id') ?? session('checkin_batch_id');
        $batchAssetIds = session('checkout_batch_assets', []) ?: session('checkin_batch_assets', []);
        
        file_put_contents(storage_path('logs/DEBUG_CHECKIN.txt'), 
            date('Y-m-d H:i:s') . " - batch_id: " . ($batchId ?? 'NULL') . " | assets_count: " . count($batchAssetIds) . "\n", 
            FILE_APPEND
        );
        
        Log::info('handleBulkEmailNotification called', [
            'type' => $type,
            'asset_id' => $event->checkoutable->id,
            'batch_id' => $batchId,
            'batch_assets_count' => count($batchAssetIds)
        ]);
        
        // Initialize batch email tracking if not exists
        if (!session()->has('email_batch_processed')) {
            session()->put('email_batch_processed', []);
        }
        
        $processedEmailBatches = session('email_batch_processed', []);
        
        // Check if this batch email was already processed
        if ($batchId && in_array($batchId, $processedEmailBatches)) {
            return;
        }

        // For bulk operations (checkout or checkin)
        if ($batchId && !empty($batchAssetIds)) {
            // Track processed assets for email
            $currentEmailAssetIds = session('email_current_assets', []);
            $currentEmailAssetIds[] = $event->checkoutable->id;
            session()->put('email_current_assets', $currentEmailAssetIds);
            
            // If not all assets have been processed yet, just return (don't send individual email)
            if (count($currentEmailAssetIds) < count($batchAssetIds)) {
                return;
            }
            
            // All assets from the batch have been processed, send bulk email
            if (count($currentEmailAssetIds) >= count($batchAssetIds)) {
                $assets = Asset::whereIn('id', $batchAssetIds)->with(['model', 'assetstatus'])->get();
                
                // Get target and admin based on operation type
                if ($type === 'checkout') {
                    $target = session('checkout_batch_target');
                    $admin = session('checkout_batch_admin');
                    $note = session('checkout_batch_note');
                    $mailable = new \App\Mail\BulkCheckoutAssetMail($assets, $target, $admin, $note);
                } else {
                    $target = null;
                    $admin = session('checkin_batch_admin');
                    $note = session('checkin_batch_note');
                    $mailable = new \App\Mail\BulkCheckinAssetMail($assets, $admin, $note);
                }
                
                $notifiable = $this->getNotifiableUser($event);
                $notifiableHasEmail = $notifiable instanceof User && $notifiable->email;
                $shouldSendEmailToUser = $shouldSendEmailToUser && $notifiableHasEmail;
                
                [$to, $cc] = $this->generateEmailRecipients($shouldSendEmailToUser, $shouldSendEmailToAlertAddress, $notifiable);
                
                if (!empty($to)) {
                    try {
                        $toMail = (clone $mailable)->locale($notifiable->locale);
                        Mail::to(array_flatten($to))->send($toMail);
                        Log::info('Bulk ' . $type . ' Mail sent to target', ['batch_id' => $batchId, 'count' => count($assets)]);
                    } catch (\Throwable $e) {
                        Log::warning("Failed to send bulk " . $type . " email to target: " . $e->getMessage());
                    }
                }
                
                if (!empty($cc)) {
                    try {
                        $ccMail = (clone $mailable)->locale(Setting::getSettings()->locale);
                        Mail::to(array_flatten($cc))->send($ccMail);
                        Log::info('Bulk ' . $type . ' Mail sent to CC', ['batch_id' => $batchId]);
                    } catch (\Throwable $e) {
                        Log::warning("Failed to send bulk " . $type . " email to CC: " . $e->getMessage());
                    }
                }
                
                // Mark batch as processed for email
                $processedEmailBatches[] = $batchId;
                session()->put('email_batch_processed', $processedEmailBatches);
                
                // Clean up ALL batch session data after sending bulk email
                session()->forget([
                    'email_current_assets',
                    'checkout_batch_id', 'checkout_batch_assets', 
                    'checkout_batch_target', 'checkout_batch_admin', 'checkout_batch_note',
                    'checkin_batch_id', 'checkin_batch_assets', 
                    'checkin_batch_admin', 'checkin_batch_note'
                ]);
                
                Log::info('Batch session cleaned up after sending bulk email', ['batch_id' => $batchId]);
                
                // CRITICAL: Return here to prevent sending individual emails
                return;
            }
            
            // If we're here, it means we're in a batch but not the last asset yet
            // So we should NOT send individual email, just return
            return;
        }
        
        // Only reach here if NOT a batch operation - send individual email
        if (true) {
            // For individual checkout/checkin, send normal email
            if ($type === 'checkout') {
                $mailable = $this->getCheckoutMailType($event, $acceptance);
            } else {
                $mailable = $this->getCheckinMailType($event);
            }
            
            $notifiable = $this->getNotifiableUser($event);
            $notifiableHasEmail = $notifiable instanceof User && $notifiable->email;
            $shouldSendEmailToUser = $shouldSendEmailToUser && $notifiableHasEmail;
            
            [$to, $cc] = $this->generateEmailRecipients($shouldSendEmailToUser, $shouldSendEmailToAlertAddress, $notifiable);
            
            if (!empty($to)) {
                try {
                    $toMail = (clone $mailable)->locale($notifiable->locale);
                    Mail::to(array_flatten($to))->send($toMail);
                    Log::info(ucfirst($type) . ' Mail sent to target');
                } catch (\Throwable $e) {
                    Log::warning("Failed to send " . $type . " email to target: " . $e->getMessage());
                }
            }
            
            if (!empty($cc)) {
                try {
                    $ccMail = (clone $mailable)->locale(Setting::getSettings()->locale);
                    Mail::to(array_flatten($cc))->send($ccMail);
                } catch (\Throwable $e) {
                    Log::warning("Failed to send " . $type . " email to CC: " . $e->getMessage());
                }
            }
        }
    }
}
