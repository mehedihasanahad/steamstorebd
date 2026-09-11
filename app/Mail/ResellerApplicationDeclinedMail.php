<?php

namespace App\Mail;

use App\Models\ResellerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResellerApplicationDeclinedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ResellerApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on your reseller application — '.$this->application->application_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reseller-application-declined',
            text: 'emails.reseller-application-declined-plain',
        );
    }
}
