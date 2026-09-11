<?php

namespace App\Mail;

use App\Models\ResellerApplication;
use App\Services\ResellerProgram;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResellerApplicationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ResellerApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Congratulations! You are now an approved reseller 🎉',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reseller-application-approved',
            text: 'emails.reseller-application-approved-plain',
            with: [
                'benefits' => ResellerProgram::fromSettings()->benefits(),
            ],
        );
    }
}
