<?php

namespace App\Support\Campaigns;

/**
 * Whether the links this application puts in a campaign can be reached from
 * outside it.
 *
 * Campaign mail carries a List-Unsubscribe header, which is what tells an
 * inbox provider the message is bulk and where to opt out. Providers check it.
 * Built from an APP_URL of http://localhost:8000 the header names a host that
 * exists on nobody else's network, and a bulk message whose unsubscribe
 * address cannot be resolved is treated as a broken bulk message -- accepted
 * by the relay, then quietly discarded rather than bounced.
 *
 * That is what a campaign sent from a development machine looks like from the
 * outside, and it is invisible from the inside: the send reports success at
 * every step. So the header is left off when the URL is not routable, and a
 * campaign send is refused outright, rather than producing mail that vanishes.
 */
final class PublicUrl
{
    /** Hosts that only ever mean "this machine". */
    private const LOOPBACK = ['localhost', '127.0.0.1', '::1', '0.0.0.0', 'host.docker.internal'];

    /** Suffixes a public resolver will not answer for. */
    private const PRIVATE_SUFFIXES = ['.local', '.localhost', '.test', '.internal', '.invalid', '.example'];

    public static function host(?string $url = null): string
    {
        return (string) parse_url($url ?? (string) config('app.url'), PHP_URL_HOST);
    }

    public static function isRoutable(?string $url = null): bool
    {
        return self::problem($url) === null;
    }

    /**
     * Why the configured URL cannot be used in mail sent to the outside world,
     * or null when it can. Phrased for whoever is looking at the admin screen.
     */
    public static function problem(?string $url = null): ?string
    {
        $host = self::host($url);

        if ($host === '') {
            return 'APP_URL is not set, so links in the message would have nowhere to point.';
        }

        if (in_array(strtolower($host), self::LOOPBACK, true)) {
            return "APP_URL points at {$host}, which only exists on this machine. "
                . 'Every link in the campaign — including the unsubscribe link mail providers check — '
                . 'would be unreachable, and the message would be accepted and then discarded.';
        }

        foreach (self::PRIVATE_SUFFIXES as $suffix) {
            if (str_ends_with(strtolower($host), $suffix)) {
                return "APP_URL points at {$host}, which is not a publicly resolvable domain. "
                    . 'Links in the campaign, including the unsubscribe link, would not resolve for recipients.';
            }
        }

        // Bare IPs and private ranges: reachable for us, not for a recipient.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                ? null
                : "APP_URL points at the private address {$host}, which recipients cannot reach.";
        }

        return null;
    }
}
