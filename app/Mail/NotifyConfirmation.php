<?php

namespace App\Mail;

use App\Models\Game;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotifyConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?Game $game,
        public string $confirmUrl,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->game
            ? "Confirm notifications for {$this->game->title}"
            : 'Confirm your MyNextPlat notifications';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notify-confirmation',
            with: [
                'gameTitle' => $this->game?->title,
                'confirmUrl' => $this->confirmUrl,
            ],
        );
    }
}
