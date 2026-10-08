<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class EditEventMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $email) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Edit your events');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.edit-event',
            with: [
                'url' => URL::temporarySignedRoute(
                    'events.manage',
                    now()->addHours((int) config('events.edit_hours')),
                    ['email' => $this->email],
                ),
            ],
        );
    }
}
