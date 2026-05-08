<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Models\GameCorrection;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class AdminInboxNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $type,
        public ContactMessage|GameCorrection $item,
        public bool $capReached = false,
        public int $cap = 0
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->type === 'contact' ? 'contact message' : 'game correction';
        $prefix = $this->capReached ? '[MyNextPlat ⚠ daily cap]' : '[MyNextPlat]';
        $subject = "{$prefix} New {$label}: " . $this->headline();

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin-inbox',
            with: [
                'type' => $this->type,
                'item' => $this->item,
                'headline' => $this->headline(),
                'adminUrl' => $this->type === 'contact'
                    ? url('/admin/contact')
                    : url('/admin/corrections'),
                'capReached' => $this->capReached,
                'cap' => $this->cap,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function headline(): string
    {
        if ($this->item instanceof ContactMessage) {
            return $this->item->subject;
        }

        return optional($this->item->game)->title ?? 'Unknown game';
    }

    public static function notifyAdmin(string $type, ContactMessage|GameCorrection $item): void
    {
        $cap = (int) config('mail.admin_inbox_daily_cap', 5);
        $key = 'admin_inbox_count:' . now()->format('Y-m-d');

        $count = Cache::increment($key);
        if ($count === false || $count === 1) {
            Cache::put($key, 1, now()->endOfDay());
            $count = 1;
        }

        if ($count > $cap) {
            return;
        }

        $to = config('mail.admin_notify_to', 'mjwaney@gmail.com');
        Mail::to($to)->send(new self($type, $item, $count >= $cap, $cap));
    }
}
