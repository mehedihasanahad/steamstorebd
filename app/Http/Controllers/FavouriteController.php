<?php

namespace App\Http\Controllers;

use App\Models\Favourite;
use App\Models\GiftCardCategory;
use Illuminate\Http\Request;

class FavouriteController extends Controller
{
    /** The shopper's saved products. */
    public function index(Request $request)
    {
        $products = GiftCardCategory::query()
            ->whereIn('id', $request->user()->favourites()->pluck('gift_card_category_id'))
            ->where('is_active', true)
            ->with('mainCategory')
            ->withMin(['giftCards as min_price_bdt' => fn ($q) => $q->where('is_active', true)], 'price_bdt')
            ->orderBy('name')
            ->get();

        return view('storefront.favourites', compact('products'));
    }

    /**
     * Add or remove one product. Idempotent in both directions: the unique
     * index means a double submit cannot duplicate a row, and removing
     * something already gone is not an error.
     */
    public function toggle(Request $request, GiftCardCategory $giftCardCategory)
    {
        $existing = Favourite::where('user_id', $request->user()->id)
            ->where('gift_card_category_id', $giftCardCategory->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return back()->with('success', 'Removed from favourites.');
        }

        Favourite::firstOrCreate([
            'user_id'               => $request->user()->id,
            'gift_card_category_id' => $giftCardCategory->id,
        ]);

        return back()->with('success', 'Saved to favourites.');
    }
}
