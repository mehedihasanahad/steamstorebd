<?php

namespace App\Jobs;

use App\Services\Competitor\PriceCheckRunner;
use App\Services\Competitor\PriceWatchRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * A sweep started by hand, from the Run now button.
 *
 * On the queue rather than in the request because a full catalogue takes
 * minutes to walk, and because the browser it drives has to start from a CLI
 * process: the headed Chromium the sweep needs is launched the same way for
 * the worker as for the scheduler, which is not true of PHP-FPM.
 *
 * The nightly run stays a scheduled command. This is the same work asked for
 * at a different moment, so it goes through the same runner and writes the
 * same rows -- a card already read today is left alone unless the admin asked
 * for a re-read, exactly as an interrupted nightly run would be resumed.
 */
class RunCompetitorPriceWatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * One attempt. A sweep that died partway is resumed simply by starting
     * another, and the rows already written are skipped -- whereas a retry
     * would silently point a second browser at a shop that may well have
     * stopped answering the first for a reason.
     */
    public int $tries = 1;

    /** A full catalogue, at a few seconds a card plus the pause between. */
    public int $timeout = 1800;

    public function __construct(public readonly bool $force = false)
    {
        // Never `default`. That queue carries the e-mail delivering the codes
        // a customer has just paid for, and a sweep holds a worker for
        // minutes -- on a single worker that is a paying customer waiting on
        // a price check. This queue has a worker to itself.
        $this->onQueue(config('competitor.queue', 'price-watch'));
    }

    /**
     * Never two sweeps at once.
     *
     * The nightly command already guards itself with withoutOverlapping, but
     * nothing stopped an admin pressing Run now at 10 PM and pointing a
     * second browser at the same shop from the same address -- which is the
     * one thing most likely to get that address refused.
     *
     * dontRelease, because a duplicate here is not work waiting its turn; the
     * sweep that is already running is doing it.
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('competitor-price-watch'))->dontRelease()->expireAfter(3600)];
    }

    public function handle(PriceCheckRunner $runner): void
    {
        PriceWatchRun::started();

        try {
            $summary = $runner->run(['force' => $this->force]);
        } catch (Throwable $e) {
            // The screen only ever sees what is put here, so a sweep that
            // died has to say so itself -- otherwise the button sits on
            // "running" until the entry expires an hour later.
            PriceWatchRun::failed($e->getMessage());

            throw $e;
        }

        PriceWatchRun::finished($summary);

        Log::info('Competitor price watch run from the admin screen', [
            'checked'       => $summary->checked,
            'opportunities' => $summary->opportunities,
            'failed'        => $summary->failed,
            'forced'        => $this->force,
        ]);
    }

    /** Covers the ways a job ends without handle() returning: timeout, kill. */
    public function failed(?Throwable $e): void
    {
        PriceWatchRun::failed($e?->getMessage() ?? 'The sweep stopped before it finished.');
    }
}
