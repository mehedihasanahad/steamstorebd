<?php

namespace App\Mail;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Support\Campaigns\MergeTags;
use App\Support\Campaigns\PublicUrl;
use App\Support\EmailRichText;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmailCampaign $campaign,
        public EmailCampaignRecipient $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->campaign->subject);
    }

    /**
     * List-Unsubscribe is what puts the unsubscribe control in Gmail's own
     * header rather than leaving people to hunt for the link — and what keeps
     * a bulk send out of the spam folder on reputation grounds.
     *
     * Without List-Unsubscribe-Post, deliberately: one-click unsubscribe is a
     * bare POST from the mail provider, and this application's unsubscribe
     * route sits behind CSRF like the rest of the site.
     */
    public function headers(): Headers
    {
        // Omitted rather than sent broken: a provider that checks this header
        // and cannot resolve it treats the message as malformed bulk mail and
        // discards it after accepting, which is worse than not declaring it.
        if (! PublicUrl::isRoutable()) {
            return new Headers;
        }

        return new Headers(text: [
            'List-Unsubscribe' => '<' . $this->unsubscribeUrl() . '>',
        ]);
    }

    public function content(): Content
    {
        $merged = MergeTags::apply(
            $this->campaign->body,
            $this->recipient->name,
            $this->recipient->email,
        );

        return new Content(
            view: 'emails.campaign',
            text: 'emails.campaign-plain',
            with: [
                'bodyHtml'       => EmailRichText::inline($merged),
                'bodyText'       => EmailRichText::toText($merged),
                'unsubscribeUrl' => $this->unsubscribeUrl(),
            ],
        );
    }

    public function unsubscribeUrl(): string
    {
        return URL::signedRoute('email.unsubscribe', [
            'email'    => $this->recipient->email,
            'campaign' => $this->campaign->id,
        ]);
    }
}
