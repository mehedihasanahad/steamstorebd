<?php

namespace App\Http\Controllers;

use App\Models\CatalogSection;
use App\Services\ExclusiveOffers;
use Illuminate\Http\Request;

/**
 * /offers — every discounted card in one place, filterable by section.
 *
 * The page is part of the Exclusive Offers programme, so it closes with the
 * programme: while the section is switched off in Site Settings the homepage
 * rail is hidden and this page is a 404, rather than a live page nothing
 * links to.
 */
class OfferController extends Controller
{
    public function index(Request $request)
    {
        $offers = ExclusiveOffers::fromSettings();

        abort_unless($offers->enabled(), 404);

        // An unknown or switched-off slug falls back to every offer instead of
        // a 404: a stale filter link should still land the shopper on offers.
        $section = filled($request->query('section'))
            ? CatalogSection::where('slug', $request->query('section'))->where('is_active', true)->first()
            : null;

        return view('storefront.offers', [
            'offers'         => $offers,
            'section'        => $section,
            'cards'          => $offers->listing($section),
            'sections'       => $offers->sections(),
            'totalOffers'    => $offers->count(),
        ]);
    }
}
