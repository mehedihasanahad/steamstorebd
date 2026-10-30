<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The confirm-your-address message.
 *
 * Laravel builds this one from its own markdown components by default, which
 * is why it arrived looking like a framework e-mail rather than like the shop.
 * It is a Mailable here so it goes through the same layout, palette and
 * plain-text half as every other message the store sends.
 */
class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $verificationUrl,
        public int $expiresInMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm your email — ' . site_setting('site_name', 'Steam Store BD'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email',
            text: 'emails.verify-email-plain',
        );
    }
}
