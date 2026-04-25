<?php

namespace App\Http\Controllers;

use App\Models\GiftCardCategory;

class SitemapController extends Controller
{
    public function index()
    {
        $categories = GiftCardCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['slug', 'updated_at']);

        return response()
            ->view('sitemap', compact('categories'))
            ->header('Content-Type', 'application/xml');
    }
}
