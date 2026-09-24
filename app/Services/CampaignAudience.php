<?php

namespace App\Services;

use App\Models\EmailCampaign;
use App\Support\Campaigns\AudienceSchema;
use App\Support\Campaigns\RuleCompiler;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Who a campaign would reach.
 *
 * Used twice with the same answer both times: to show a count while the
 * campaign is being written, and to freeze the recipient rows when it is sent.
 * The suppression list is subtracted here rather than at send time so the
 * number on the screen is the number of people who will actually be written
 * to — though the send checks again, because someone can unsubscribe while
 * their campaign is still working through the queue.
 */
class CampaignAudience
{
    public function __construct(private readonly RuleCompiler $compiler = new RuleCompiler) {}

    public function count(EmailCampaign $campaign): int
    {
        if ($campaign->audience === EmailCampaign::AUDIENCE_MANUAL) {
            return $this->manualRecipients($campaign)->count();
        }

        return $this->query($campaign)->count();
    }

    /** A sample for the admin to eyeball before committing to a send. */
    public function preview(EmailCampaign $campaign, int $limit = 25): Collection
    {
        if ($campaign->audience === EmailCampaign::AUDIENCE_MANUAL) {
            return $this->manualRecipients($campaign)->take($limit)->values();
        }

        return collect($this->query($campaign)->orderBy('email')->limit($limit)->get())
            ->map(fn ($row) => ['email' => $row->email, 'name' => $row->name ?: null])
            ->values();
    }

    /**
     * Walks the whole audience in chunks. Campaigns can be large and the rows
     * are only needed long enough to be written to the recipients table.
     *
     * @param  callable(Collection<int,array{email:string,name:?string}>):void  $callback
     */
    public function each(EmailCampaign $campaign, callable $callback, int $chunkSize = 500): void
    {
        if ($campaign->audience === EmailCampaign::AUDIENCE_MANUAL) {
            $this->manualRecipients($campaign)
                ->chunk($chunkSize)
                ->each(fn (Collection $chunk) => $callback($chunk->values()));

            return;
        }

        $this->query($campaign)
            ->orderBy('email')
            ->chunk($chunkSize, function (Collection $rows) use ($callback) {
                $callback($rows->map(fn ($row) => [
                    'email' => $row->email,
                    'name'  => $row->name ?: null,
                ])->values());
            });
    }

    /** The compiled audience query, suppression already subtracted. */
    public function query(EmailCampaign $campaign): Builder
    {
        $schema = AudienceSchema::for($campaign->audience);
        $query  = $schema->baseQuery();

        $this->compiler->apply($query, $campaign->filters, $schema);

        return $this->withoutUnsubscribed($query);
    }

    private function withoutUnsubscribed(Builder $query): Builder
    {
        return $query->whereNotExists(
            DB::table('email_unsubscribes')
                ->selectRaw('1')
                ->whereRaw('LOWER(TRIM(email_unsubscribes.email)) = c.email'),
        );
    }

    /**
     * Accepts one address per line or separated by commas or semicolons, bare
     * or in "Name <address>" form, which is what comes out of a mail client
     * when someone copies a list of people.
     *
     * @return Collection<int,array{email:string,name:?string}>
     */
    public function manualRecipients(EmailCampaign $campaign): Collection
    {
        $suppressed = DB::table('email_unsubscribes')
            ->pluck('email')
            ->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->flip();

        return collect(preg_split('/[\r\n,;]+/', (string) $campaign->manual_recipients) ?: [])
            ->map(fn (string $line) => $this->parseAddress($line))
            ->filter()
            ->unique(fn (array $row) => $row['email'])
            ->reject(fn (array $row) => $suppressed->has($row['email']))
            ->values();
    }

    /** @return array{email:string,name:?string}|null */
    private function parseAddress(string $line): ?array
    {
        $line = trim($line);

        if ($line === '') {
            return null;
        }

        $name = null;

        if (preg_match('/^(.*?)<([^>]+)>$/', $line, $match)) {
            $name = trim($match[1], " \t\"'") ?: null;
            $line = trim($match[2]);
        }

        $email = mb_strtolower($line);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? ['email' => $email, 'name' => $name] : null;
    }
}
