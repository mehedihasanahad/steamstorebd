<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Jobs\SendAdminContactMessageEmail;
use App\Models\Banner;
use App\Models\ContactMessage;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\Review;
use App\Services\PaymentMethods;
use App\Services\StorefrontCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class StorefrontController extends Controller
{
    /** Deals and featured rails are short by design: a rail nobody scrolls sells nothing. */
    private const RAIL_LIMIT = 12;

    public function home(StorefrontCatalog $catalog)
    {
        $mainCategories = $catalog->brands();

        // Fallback: show gift card categories directly if no main categories are set up yet.
        // This is the safety net on a production database where the section backfill
        // has not run, so it must keep working exactly as it did.
        $fallbackCategories = $mainCategories->isEmpty()
            ? Cache::remember('home_categories', 300, function () {
                return GiftCardCategory::with(['giftCards' => function ($q) {
                    $q->where('is_active', true)->orderBy('price_bdt');
                }])
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->filter(fn ($cat) => $cat->giftCards->isNotEmpty())
                    ->values();
            })
            : collect();

        return view('storefront.home', [
            'mainCategories'       => $mainCategories,
            'fallbackCategories'   => $fallbackCategories,
            'sections'             => $catalog->sectionsWithBrands(),
            'banners'              => Banner::visible()->get(),
            'deals'                => $this->deals(),
            'featuredProducts'     => $this->featuredProducts(),
            'reviews'              => $this->reviews(),
            'activePaymentMethods' => PaymentMethods::active(),
        ]);
    }

    public function faq()
    {
        return view('storefront.faq');
    }

    public function howToRedeem()
    {
        return view('storefront.how-to-redeem');
    }

    public function contact()
    {
        return view('storefront.contact');
    }

    public function contactSubmit(ContactMessageRequest $request)
    {
        $contactMessage = ContactMessage::create($request->messageAttributes());

        dispatch(new SendAdminContactMessageEmail($contactMessage));

        return back()->with('success', 'Your message has been sent! We\'ll get back to you soon.');
    }

    /**
     * Discounted cards, newest saving first. A deal is derived from the two
     * prices rather than flagged, so it disappears on its own the moment the
     * compare-at price stops being higher.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, GiftCard>
     */
    private function deals()
    {
        return GiftCard::query()
            ->active()
            ->deals()
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true))
            ->with('category.mainCategory')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit(self::RAIL_LIMIT)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, GiftCardCategory>
     */
    private function featuredProducts()
    {
        return GiftCardCategory::featured()
            ->whereHas('giftCards', fn (Builder $q) => $q->where('is_active', true))
            ->with('mainCategory')
            ->withMin(['giftCards as min_price_bdt' => fn (Builder $q) => $q->where('is_active', true)], 'price_bdt')
            ->limit(self::RAIL_LIMIT)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Review>
     */
    private function reviews()
    {
        return Review::approved()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();
    }
}
