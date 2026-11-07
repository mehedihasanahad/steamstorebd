<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare Turnstile token verification.
 *
 * Turnstile decides in the browser whether the visitor looks human and hands
 * the form a single-use token. There is no puzzle to solve and no score to
 * interpret -- siteverify answers yes or no. That suits us: the scripted
 * sign-ups that reserved our stock cannot mint a token at all.
 *
 * Switched off entirely when no keys are configured, so local work and the
 * test suite never call out to Cloudflare.
 */
class Turnstile
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /** The name of the hidden input the widget fills in. */
    public const FIELD = 'turnstile_token';

    /** Both keys have to be present; half a configuration is a misconfiguration. */
    public static function enabled(): bool
    {
        return filled(config('services.turnstile.site_key'))
            && filled(config('services.turnstile.secret_key'));
    }

    public static function siteKey(): ?string
    {
        return config('services.turnstile.site_key');
    }

    /**
     * Is this a real, unused, in-date token minted for `$action`?
     *
     * Returns true when Turnstile is switched off, so callers may apply the
     * rule unconditionally.
     */
    public function verify(?string $token, string $action, ?string $ip = null): bool
    {
        if (! self::enabled()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('services.turnstile.timeout', 5))
                ->post(self::VERIFY_URL, array_filter([
                    'secret'   => config('services.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $ip,
                ]));
        } catch (\Throwable $e) {
            // Cloudflare being unreachable is our outage, not the visitor's.
            // Let them through and say so in the log rather than closing the
            // shop to everyone.
            Log::warning('Turnstile unreachable, request allowed through', ['error' => $e->getMessage()]);

            return (bool) config('services.turnstile.fail_open', true);
        }

        if ($response->failed()) {
            Log::warning('Turnstile siteverify returned an HTTP error', ['status' => $response->status()]);

            return (bool) config('services.turnstile.fail_open', true);
        }

        $body = (array) $response->json();

        if (! ($body['success'] ?? false)) {
            Log::info('Turnstile rejected the token', ['errors' => $body['error-codes'] ?? []]);

            return false;
        }

        // Turnstile echoes back the action the widget was rendered with.
        // Without this check a token lifted from a cheap public form could be
        // replayed against registration.
        if (($body['action'] ?? null) !== $action) {
            Log::info('Turnstile action mismatch', [
                'expected' => $action,
                'received' => $body['action'] ?? null,
            ]);

            return false;
        }

        return true;
    }
}
