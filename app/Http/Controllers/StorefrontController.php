<?php

namespace App\Http\Controllers;

use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StorefrontController extends Controller
{
    public function home()
    {
        $categories = Cache::remember('home_categories', 300, fn() =>
            GiftCardCategory::with(['giftCards' => function ($q) {
                $q->where('is_active', true)->orderBy('price_bdt');
            }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn($cat) => $cat->giftCards->isNotEmpty())
            ->values()
        );

        return view('storefront.home', compact('categories'));
    }

    public function product(string $categorySlug)
    {
        $category = GiftCardCategory::where('slug', $categorySlug)
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
