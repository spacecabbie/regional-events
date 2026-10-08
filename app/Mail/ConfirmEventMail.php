<?php

namespace App\Mail;

use App\Events\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ConfirmEventMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Event $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirm your event');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.confirm-event',
            with: [
                'event' => $this->event,
                'url' => URL::temporarySignedRoute(
                    'events.confirm.show',
                    now()->addHours((int) config('events.confirm_hours')),
                    ['event' => $this->event],
                ),
            ],
        );
    }
}
