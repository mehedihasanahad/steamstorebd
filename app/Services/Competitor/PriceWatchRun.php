<?php

namespace App\Services\Competitor;

use Illuminate\Support\Facades\Cache;

/**
 * What the on-demand sweep is doing, for a screen in another process.
 *
 * The sweep runs on the queue worker and the button that started it lives in
 * a web request, so the two share nothing but the cache -- which is the
 * database here, and therefore survives both of them. Without this the admin
 * presses Run now, sees a notification, and then has no way of telling a
 * sweep that is walking the catalogue from a queue worker that is not
 * running at all.
 *
 * Deliberately not a table. This is the state of one button over the next
 * few minutes, not a record anybody will want next week; the readings
 * themselves are the history and they are already stored.
 */
final class PriceWatchRun
{
    private const KEY = 'competitor_price_watch_run';

    /**
     * Long enough to outlast a sweep over a full catalogue, short enough
     * that a worker killed mid-run does not leave the button disabled for
     * the rest of the day.
     */
    private const TTL = 3600;

    public static function queued(): void
    {
        self::put(['state' => 'queued', 'at' => now()->toDateTimeString()]);
    }

    public static function started(): void
    {
        self::put(['state' => 'running', 'at' => now()->toDateTimeString()]);
    }

    public static function finished(PriceCheckSummary $summary): void
    {
        self::put([
            'state'         => 'finished',
            'at'            => now()->toDateTimeString(),
            'checked'       => $summary->checked,
            'opportunities' => $summary->opportunities,
            'failed'        => $summary->failed,
        ]);
    }

    public static function failed(string $message): void
    {
        self::put([
            'state'   => 'failed',
            'at'      => now()->toDateTimeString(),
            'message' => $message,
        ]);
    }

    public static function clear(): void
    {
        Cache::forget(self::KEY);
    }

    /** @return array<string, mixed>|null */
    public static function current(): ?array
    {
        $run = Cache::get(self::KEY);

        return is_array($run) ? $run : null;
    }

    /**
     * Whether a sweep started from the button is still outstanding.
     *
     * "Queued" counts: a job waiting for a worker is a sweep the admin has
     * already asked for, and offering the button again would only stack a
     * second one behind it.
     */
    public static function inFlight(): bool
    {
        return in_array(self::current()['state'] ?? null, ['queued', 'running'], true);
    }

    /** @param array<string, mixed> $run */
    private static function put(array $run): void
    {
        Cache::put(self::KEY, $run, self::TTL);
    }
}
