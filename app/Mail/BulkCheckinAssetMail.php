<?php

namespace App\Mail;

use App\Helpers\Helper;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class BulkCheckinAssetMail extends Mailable
{
    use Queueable, SerializesModels;

    private Collection $assets;
    private User $admin;
    private ?string $note;
    private Setting $settings;

    /**
     * Create a new message instance.
     */
    public function __construct(Collection $assets, User $admin, ?string $note = null)
    {
        $this->assets = $assets;
        $this->admin = $admin;
        $this->note = $note;
        $this->settings = Setting::getSettings();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $from = new Address(config('mail.from.address'), config('mail.from.name'));

        return new Envelope(
            from: $from,
            subject: trans('mail.Bulk_Asset_Checkin_Notification', ['count' => $this->assets->count()]),
        );
    }

    /**
     * Get the mail representation of the notification.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.markdown.bulk-checkin-asset',
            with: [
                'assets' => $this->assets,
                'admin' => $this->admin,
                'note' => $this->note,
                'count' => $this->assets->count(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
