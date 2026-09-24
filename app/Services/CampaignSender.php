<?php

namespace App\Services;

use App\Jobs\SendCampaignEmail;
use App\Mail\CampaignMail;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Turning a written campaign into messages.
 *
 * Sending happens in two steps that are deliberately separate. Freezing writes
 * one row per recipient and is the point of no return: from then on the
 * audience cannot drift, the count is fixed, and every address has somewhere
 * to record what happened to it. Only then is anything queued.
 *
 * Nothing here sends an e-mail itself. One job per recipient is what makes a
 * single bad address a single failure rather than the end of the run, and what
 * lets the queue's own rate limiting pace the send.
 */
class CampaignSender
{
    public function __construct(private readonly CampaignAudience $audience) {}

    /**
     * Writes the recipient rows and moves the campaign to sending.
     *
     * @return int how many people it will be sent to
     */
    public function freeze(EmailCampaign $campaign): int
    {
        $total = 0;

        $this->audience->each($campaign, function (Collection $chunk) use ($campaign, &$total) {
            $now  = now();
            $rows = $chunk->map(fn (array $row) => [
                'email_campaign_id' => $campaign->id,
                'email'             => $row['email'],
                'name'              => $row['name'],
                'status'            => EmailCampaignRecipient::STATUS_PENDING,
                'created_at'        => $now,
                'updated_at'        => $now,
            ])->all();

            // insertOrIgnore, not insert: the unique key is the last line of
            // defence against the same address being written to twice.
            DB::table('email_campaign_recipients')->insertOrIgnore($rows);

            $total += count($rows);
        });

        $campaign->forceFill([
            'status'           => EmailCampaign::STATUS_SENDING,
            'started_at'       => now(),
            'completed_at'     => null,
            'total_recipients' => $campaign->recipients()->count(),
            'sent_count'       => 0,
            'failed_count'     => 0,
        ])->save();

        return $campaign->total_recipients;
    }

    /** Freeze, then queue one job per pending recipient. */
    public function send(EmailCampaign $campaign): int
    {
        $total = $this->freeze($campaign);

        if ($total === 0) {
            $this->finish($campaign);

            return 0;
        }

        $this->queuePending($campaign);

        return $total;
    }

    public function schedule(EmailCampaign $campaign, Carbon $when): void
    {
        $campaign->forceFill([
            'status'        => EmailCampaign::STATUS_SCHEDULED,
            'scheduled_for' => $when,
        ])->save();
    }

    public function unschedule(EmailCampaign $campaign): void
    {
        $campaign->forceFill([
            'status'        => EmailCampaign::STATUS_DRAFT,
            'scheduled_for' => null,
        ])->save();
    }

    /**
     * Stops a send in flight. Anything still waiting is marked skipped rather
     * than left pending, so the jobs already on the queue find nothing to do
     * and the campaign can reach a final state.
     */
    public function cancel(EmailCampaign $campaign): void
    {
        $campaign->recipients()->pending()->update([
            'status'     => EmailCampaignRecipient::STATUS_SKIPPED,
            'updated_at' => now(),
        ]);

        $campaign->forceFill([
            'status'       => EmailCampaign::STATUS_CANCELLED,
            'completed_at' => now(),
        ])->save();
    }

    /** Puts the failures back in the queue, without touching what succeeded. */
    public function retryFailed(EmailCampaign $campaign): int
    {
        $count = $campaign->recipients()->failed()->count();

        if ($count === 0) {
            return 0;
        }

        $campaign->recipients()->failed()->update([
            'status'     => EmailCampaignRecipient::STATUS_PENDING,
            'error'      => null,
            'updated_at' => now(),
        ]);

        $campaign->forceFill([
            'status'       => EmailCampaign::STATUS_SENDING,
            'completed_at' => null,
            'failed_count' => max(0, $campaign->failed_count - $count),
        ])->save();

        $this->queuePending($campaign);

        return $count;
    }

    /** Called by the job after each recipient reaches a final state. */
    public function finishIfDone(?EmailCampaign $campaign): void
    {
        if (! $campaign || ! $campaign->isSending()) {
            return;
        }

        if ($campaign->recipients()->pending()->exists()) {
            return;
        }

        $this->finish($campaign);
    }

    private function finish(EmailCampaign $campaign): void
    {
        $campaign->forceFill([
            'status'       => EmailCampaign::STATUS_SENT,
            'completed_at' => now(),
        ])->save();
    }

    private function queuePending(EmailCampaign $campaign): void
    {
        $campaign->recipients()->pending()
            ->orderBy('id')
            ->chunkById(500, function (Collection $recipients) {
                foreach ($recipients as $recipient) {
                    SendCampaignEmail::dispatch($recipient);
                }
            });
    }

    /**
     * A single message to one address, for checking the thing before it goes
     * to everybody. The recipient row is never saved — it exists only to give
     * the mailable the shape it expects.
     */
    public function test(EmailCampaign $campaign, User $admin): void
    {
        $recipient = new EmailCampaignRecipient([
            'email_campaign_id' => $campaign->id,
            'email'             => $admin->email,
            'name'              => $admin->name,
            'status'            => EmailCampaignRecipient::STATUS_PENDING,
        ]);

        $recipient->setRelation('campaign', $campaign);

        Mail::to($admin->email, $admin->name)->send(new CampaignMail($campaign, $recipient));
    }
}
