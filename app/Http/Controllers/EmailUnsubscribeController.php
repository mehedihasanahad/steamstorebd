<?php

namespace App\Http\Controllers;

use App\Models\EmailUnsubscribe;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Leaving the campaign list.
 *
 * Two steps on purpose. Corporate mail gateways and link scanners follow every
 * URL in a message before it reaches anyone, so a one-click GET would
 * unsubscribe people who never opened the e-mail. The link opens a page, and a
 * form posts back to the same signed URL to make it stick.
 *
 * The link is signed rather than tokenised: there is no row to hang a token on
 * until somebody actually unsubscribes, and the signature already makes the
 * address in the URL impossible to tamper with.
 */
class EmailUnsubscribeController extends Controller
{
    public function show(Request $request): View
    {
        return view('storefront.unsubscribe', [
            'email'      => (string) $request->query('email'),
            'alreadyOut' => EmailUnsubscribe::has((string) $request->query('email')),
        ]);
    }

    public function store(Request $request): View
    {
        $email = (string) $request->query('email');

        EmailUnsubscribe::add(
            $email,
            $request->query('campaign') ? (int) $request->query('campaign') : null,
            $request->input('reason'),
        );

        return view('storefront.unsubscribe', [
            'email'      => $email,
            'alreadyOut' => true,
            'justNow'    => true,
        ]);
    }
}
