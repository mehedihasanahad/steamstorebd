<?php

namespace App\Console\Commands;

use App\Models\EmailCampaign;
use App\Services\CampaignSender;
use App\Support\Campaigns\PublicUrl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Starts the campaigns whose time has come.
 *
 * Scheduling only records an intention; this is what acts on it. It runs every
 * minute and takes each due campaign one at a time, because freezing an
 * audience is the expensive part and a minute is plenty of room for it.
 */
class SendDueCampaigns extends Command
{
    protected $signature = 'campaigns:send-due';

    protected $description = 'Send any e-mail campaign whose scheduled time has passed';

    public function handle(CampaignSender $sender): int
    {
        $due = EmailCampaign::due()->orderBy('scheduled_for')->get();

        if ($due->isEmpty()) {
            return self::SUCCESS;
        }

        // Whoever scheduled these was warned at the time, so they go out.
        // Recorded all the same: if the links are unreachable the messages are
        // very likely to be discarded, and this is the only trace of why.
        if ($problem = PublicUrl::problem()) {
            $this->warn('Links may be unreachable: ' . $problem);
            Log::warning('Campaign sent with unreachable links: ' . $problem, [
                'campaigns' => $due->pluck('id')->all(),
            ]);
        }

        foreach ($due as $campaign) {
            // Claimed before the audience is resolved: freezing a large list
            // can outlast the minute, and a second run must not start the same
            // campaign again.
            $claimed = EmailCampaign::whereKey($campaign->id)
                ->where('status', EmailCampaign::STATUS_SCHEDULED)
                ->update(['status' => EmailCampaign::STATUS_SENDING, 'started_at' => now()]);

            if (! $claimed) {
                continue;
            }

            $total = $sender->send($campaign->fresh());

            $this->info("Campaign [{$campaign->name}] queued for {$total} recipients.");
        }

        return self::SUCCESS;
    }
}
