<?php

namespace App\Jobs;

use App\Mail\CampaignMail;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailUnsubscribe;
use App\Services\CampaignSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * One campaign message to one address.
 *
 * Per recipient rather than per campaign so that a single address that bounces
 * or times out costs one retry instead of taking the rest of the send down
 * with it, and so the queue's rate limiter can pace the whole thing.
 *
 * It runs on its own queue, behind the transactional mail. A campaign to
 * thousands of people must never leave somebody waiting for the code they just
 * paid for -- see the worker's --queue order in supervisor.conf.
 */
class SendCampaignEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public EmailCampaignRecipient $recipient)
    {
        $this->onQueue(config('mail.campaign.queue', 'campaigns'));
    }

    public function middleware(): array
    {
        return [new RateLimited('email-campaign')];
    }

    public function handle(CampaignSender $sender): void
    {
        $recipient = $this->recipient->fresh();

        // Already handled, or cancelled out from under us while queued.
        if (! $recipient || ! $recipient->isPending()) {
            return;
        }

        $campaign = $recipient->campaign;

        if (! $campaign || ! $campaign->isSending()) {
            return;
        }

        // Checked again here and not only when the audience was frozen: a
        // large send takes time, and somebody may have unsubscribed during it.
        if (EmailUnsubscribe::has($recipient->email)) {
            $recipient->update(['status' => EmailCampaignRecipient::STATUS_SKIPPED]);
            $sender->finishIfDone($campaign);

            return;
        }

        Mail::to($recipient->email, $recipient->name)->send(new CampaignMail($campaign, $recipient));

        $recipient->update([
            'status'  => EmailCampaignRecipient::STATUS_SENT,
            'sent_at' => now(),
            'error'   => null,
        ]);

        $campaign->increment('sent_count');

        $sender->finishIfDone($campaign->fresh());
    }

    /** Only reached once the retries are spent, so this is the final word. */
    public function failed(Throwable $exception): void
    {
        $recipient = $this->recipient->fresh();

        if (! $recipient || ! $recipient->isPending()) {
            return;
        }

        $recipient->update([
            'status' => EmailCampaignRecipient::STATUS_FAILED,
            'error'  => Str::limit($exception->getMessage(), 500),
        ]);

        $campaign = $recipient->campaign;

        if ($campaign) {
            $campaign->increment('failed_count');
            app(CampaignSender::class)->finishIfDone($campaign->fresh());
        }
    }
}
