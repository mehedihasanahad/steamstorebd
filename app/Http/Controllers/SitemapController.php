<?php

namespace App\Http\Controllers;

use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\MainCategory;

class SitemapController extends Controller
{
    public function index()
    {
        $mainCategories = MainCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['slug', 'updated_at']);

        $categories = GiftCardCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['slug', 'updated_at']);

        $cards = GiftCard::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['slug', 'updated_at']);

        return response()
            ->view('sitemap', compact('mainCategories', 'categories', 'cards'))
            ->header('Content-Type', 'application/xml');
    }
}
