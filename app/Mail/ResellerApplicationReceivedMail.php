<?php

namespace App\Mail;

use App\Models\ResellerApplication;
use App\Services\ResellerProgram;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResellerApplicationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ResellerApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reseller Application Received — '.$this->application->application_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reseller-application-received',
            text: 'emails.reseller-application-received-plain',
            with: [
                'responseTime' => ResellerProgram::fromSettings()->responseTime(),
            ],
        );
    }
}
