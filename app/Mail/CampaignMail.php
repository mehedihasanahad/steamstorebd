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
     * List-Unsubscribe puts the unsubscribe control in Gmail's own header
     * rather than leaving people to hunt for the link. It is also a
     * declaration that the message is bulk, and that is not free: a provider
     * told a message is bulk stops extending it the leniency it gives order
     * mail and starts requiring SPF, DKIM and DMARC that line up with the
     * relay the message actually came from. Fail those as bulk and the
     * message is dropped; fail them as transactional and it usually still
     * arrives. That asymmetry is why order mail from this application lands
     * while campaigns from the same host and the same address do not.
     *
     * So it is opt-in, and off until the sending domain authenticates for its
     * relay. The unsubscribe link in the body is unaffected either way, which
     * is what recipients actually use and what keeps the send honest.
     *
     * Without List-Unsubscribe-Post, deliberately: one-click unsubscribe is a
     * bare POST from the mail provider, and this application's unsubscribe
     * route sits behind CSRF like the rest of the site.
     */
    public function headers(): Headers
    {
        if (! config('mail.campaign.list_unsubscribe')) {
            return new Headers;
        }

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
