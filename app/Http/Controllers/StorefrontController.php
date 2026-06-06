<?php

namespace App\Http\Controllers;

use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\MainCategory;
use App\Models\Review;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StorefrontController extends Controller
{
    public function home()
    {
        $mainCategories = Cache::remember('home_main_categories', 300, function () {
            return MainCategory::with(['giftCardCategories' => function ($q) {
                $q->where('is_active', true)
                    ->with(['giftCards' => function ($q) { $q->where('is_active', true)->orderBy('price_bdt'); }]);
            }])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->filter(function ($mc) {
                    return $mc->giftCardCategories
                        ->filter(fn($cat) => $cat->giftCards->isNotEmpty())
                        ->isNotEmpty();
                })
                ->values();
        });

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

        $activePaymentMethods = [];
        if (SiteSetting::get('payment_bkash_online_enabled', true) || SiteSetting::get('payment_bkash_send_money_enabled', false)) {
            $activePaymentMethods[] = ['name' => 'bKash', 'logo' => 'bkash-logo.png', 'round' => true];
        }
        if (SiteSetting::get('payment_nagad_send_money_enabled', false)) {
            $activePaymentMethods[] = ['name' => 'Nagad', 'logo' => 'nagad-logo.webp', 'round' => false];
        }
        if (SiteSetting::get('payment_rocket_send_money_enabled', false)) {
            $activePaymentMethods[] = ['name' => 'Rocket', 'logo' => 'rocket-logo.png', 'round' => true];
        }
        if (SiteSetting::get('payment_bkash_online_enabled', true)) {
            $activePaymentMethods[] = ['name' => 'VISA / MC', 'logo' => null, 'round' => false];
        }

        return view('storefront.home', compact('mainCategories', 'fallbackCategories', 'reviews', 'activePaymentMethods'));
    }

    public function brand(string $mainCategorySlug)
    {
        $mainCategory = MainCategory::where('slug', $mainCategorySlug)
            ->where('is_active', true)
            ->firstOrFail();

        $categories = GiftCardCategory::with(['giftCards' => function ($q) {
            $q->where('is_active', true)->orderBy('price_bdt');
        }])
            ->where('main_category_id', $mainCategory->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn($cat) => $cat->giftCards->isNotEmpty())
            ->values();

        return view('storefront.brand', compact('mainCategory', 'categories'));
    }

    public function product(string $categorySlug)
    {
        $category = GiftCardCategory::with('mainCategory')
            ->where('slug', $categorySlug)
            ->where('is_active', true)
            ->firstOrFail();

        $denominations = GiftCard::where('category_id', $category->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price_bdt')
            ->get();

        return view('storefront.product', compact('category', 'denominations'));
    }

    public function cardDetail(string $slug)
    {
        $card = GiftCard::with('category')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return redirect()->route('product', $card->category->slug);
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

    public function contactSubmit(Request $request)
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:100'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        return back()->with('success', 'Your message has been sent! We\'ll get back to you soon.');
    }
}
