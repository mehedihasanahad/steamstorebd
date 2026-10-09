<?php

namespace App\Services\Competitor;

use App\Models\SiteSetting;
use Throwable;

/**
 * When the nightly sweep runs, as the admin screen sets it.
 *
 * The two knobs that a shop owner actually turns -- whether it runs at all,
 * and at what hour -- moved out of .env, because changing them there means a
 * deploy and the person who wants them changed is not the person who deploys.
 * Everything else about the sweep is still environment: a proxy, a browser
 * path and a node binary are facts about the machine, not decisions.
 *
 * config/competitor.php stays the fallback rather than being replaced. A
 * setting nobody has saved reads as whatever .env already said, so this
 * changes no behaviour until somebody opens the screen and chooses.
 */
final class PriceWatchSchedule
{
    public const ENABLED_KEY = 'competitor_schedule_enabled';

    public const TIME_KEY = 'competitor_schedule_time';

    /** The settings group these are stored under. */
    public const GROUP = 'competitor';

    /**
     * The hours offered on the settings screen.
     *
     * Whole hours only. A sweep is read over breakfast and acted on during
     * the day, so the difference between 10:00 PM and 10:20 PM is not a
     * difference anybody is making a decision on -- and a free text time is
     * one typo away from an expression the scheduler refuses.
     */
    public static function timeOptions(): array
    {
        return collect(range(0, 23))
            ->mapWithKeys(fn (int $hour) => [
                sprintf('%02d:00', $hour) => date('g:i A', mktime($hour, 0)),
            ])
            ->all();
    }

    public static function enabled(): bool
    {
        $stored = self::stored(self::ENABLED_KEY);

        // Not "=== null ?: config", because '0' is a deliberate answer and an
        // empty string is not. Only a key nobody has saved defers to .env.
        return $stored === null || $stored === ''
            ? (bool) config('competitor.schedule.enabled', true)
            : (bool) $stored;
    }

    /** The hour the sweep starts, as "HH:MM" in shop time. */
    public static function time(): string
    {
        $stored = self::stored(self::TIME_KEY);

        $time = filled($stored) ? (string) $stored : (string) config('competitor.schedule.time', '22:00');

        // A malformed time would reach Schedule::dailyAt() and throw there,
        // which is in routes/console.php -- so it would not break the sweep,
        // it would break every artisan command on the box, including the one
        // somebody would run to fix it.
        return self::looksLikeTime($time) ? $time : '22:00';
    }

    private static function looksLikeTime(string $value): bool
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value);
    }

    /**
     * Reads a setting, or null when the database cannot answer.
     *
     * routes/console.php asks these questions on every artisan command, which
     * includes the migrate that creates the table they live in. A fresh
     * checkout would otherwise fail to migrate at all.
     */
    private static function stored(string $key): ?string
    {
        try {
            $value = SiteSetting::get($key);
        } catch (Throwable) {
            return null;
        }

        return $value === null ? null : (string) $value;
    }
}
