<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends GET requests that arrive on a non-canonical host or scheme (www vs
 * bare domain, http vs https) to the same path on APP_URL with a 301, so
 * search engines only ever index one copy of each page.
 *
 * Off unless APP_CANONICAL_REDIRECT=true. Behind a TLS-terminating proxy the
 * proxy must forward X-Forwarded-Proto, otherwise every https request looks
 * like http and redirects forever.
 */
class RedirectToCanonicalUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.canonical_redirect') || ! $request->isMethodSafe() || $request->is('up')) {
            return $next($request);
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        $host = parse_url($appUrl, PHP_URL_HOST);
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';

        if (! $host || ($request->getHost() === $host && $request->getScheme() === $scheme)) {
            return $next($request);
        }

        return redirect()->to($appUrl.$request->getRequestUri(), 301);
    }
}
