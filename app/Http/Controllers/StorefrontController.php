<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Jobs\SendAdminContactMessageEmail;
use App\Models\ContactMessage;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\MainCategory;
use App\Models\Review;
use App\Models\SlugRedirect;
use App\Services\PaymentMethods;
use App\Services\ReferralService;
use App\Services\StorefrontCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class StorefrontController extends Controller
{
    public function home(StorefrontCatalog $catalog)
    {
        $mainCategories = $catalog->brands();

        // Fallback: show gift card categories directly if no main categories are set up yet
        $fallbackCategories = $mainCategories->isEmpty()
            ? Cache::remember('home_categories', 300, function () {
                return GiftCardCategory::with(['giftCards' => function ($q) {
                    $q->where('is_active', true)->orderBy('price_bdt');
                }])
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->filter(fn($cat) => $cat->giftCards->isNotEmpty())
                    ->values();
            })
            : collect();

        $reviews = Review::approved()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        $activePaymentMethods = PaymentMethods::active();

        return view('storefront.home', compact('mainCategories', 'fallbackCategories', 'reviews', 'activePaymentMethods'));
    }

    public function brand(string $mainCategorySlug)
    {
        $mainCategory = MainCategory::where('slug', $mainCategorySlug)
            ->where('is_active', true)
            ->first();

        if ($mainCategory === null) {
            $moved = SlugRedirect::findModel(MainCategory::class, $mainCategorySlug);
            abort_unless($moved?->is_active, 404);

            return redirect()->route('brand', $moved->slug, 301);
        }

        $categories = GiftCardCategory::with(['giftCards' => function ($q) {
            $q->where('is_active', true)->withAvailableCodesCount()->orderBy('price_bdt');
        }])
            ->where('main_category_id', $mainCategory->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn($cat) => $cat->giftCards->isNotEmpty())
            ->values();

        $paymentMethodNames = PaymentMethods::walletNames();

        return view('storefront.brand', compact('mainCategory', 'categories', 'paymentMethodNames'));
    }

    public function product(string $categorySlug)
    {
        $category = GiftCardCategory::with('mainCategory')
            ->where('slug', $categorySlug)
            ->where('is_active', true)
            ->first();

        if ($category === null) {
            $moved = SlugRedirect::findModel(GiftCardCategory::class, $categorySlug);
            abort_unless($moved?->is_active, 404);

            return redirect()->route('product', $moved->slug, 301);
        }

        $denominations = GiftCard::where('category_id', $category->id)
            ->where('is_active', true)
            ->withAvailableCodesCount()
            ->orderBy('sort_order')
            ->orderBy('price_bdt')
            ->get();

        $relatedCategories = $category->main_category_id
            ? GiftCardCategory::where('main_category_id', $category->main_category_id)
                ->whereKeyNot($category->id)
                ->where('is_active', true)
                ->whereHas('giftCards', fn ($q) => $q->where('is_active', true))
                ->withMin(['giftCards as min_price_bdt' => fn ($q) => $q->where('is_active', true)], 'price_bdt')
                ->orderBy('sort_order')
                ->limit(8)
                ->get()
            : collect();

        $paymentMethodNames = PaymentMethods::walletNames();
        $referralSettings   = app(ReferralService::class)->getSettings();
        $referralCode       = Auth::check() ? Auth::user()->referral_code : null;

        return view('storefront.product', compact('category', 'denominations', 'relatedCategories', 'paymentMethodNames', 'referralSettings', 'referralCode'));
    }

    public function cardDetail(string $slug)
    {
        $card = GiftCard::with('category')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return redirect()->route('product', $card->category->slug, 301);
    }

    /**
     * The old /shop/{category} URLs: send each to the product or brand it
     * named, so links and rankings they earned carry over.
     */
    public function legacyShop(string $path)
    {
        $slug = Str::of($path)->trim('/')->afterLast('/')->value();

        $category = GiftCardCategory::where('slug', $slug)->where('is_active', true)->first();
        if ($category) {
            return redirect()->route('product', $category->slug, 301);
        }

        $brand = MainCategory::where('slug', $slug)->where('is_active', true)->first();
        if ($brand) {
            return redirect()->route('brand', $brand->slug, 301);
        }

        return redirect()->route('home', [], 301);
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
}
