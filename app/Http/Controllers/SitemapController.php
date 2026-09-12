<?php

namespace App\Http\Controllers;

use App\Models\GiftCardCategory;
use App\Models\MainCategory;
use App\Services\ResellerProgram;
use App\Services\StorefrontCatalog;
use Carbon\CarbonInterface;

class SitemapController extends Controller
{
    public function index(StorefrontCatalog $catalog)
    {
        $brands   = $catalog->brands();
        $products = $brands->flatMap(fn (MainCategory $brand) => $brand->giftCardCategories)
            ->concat($catalog->unbrandedCategories());

        $productUrls = $products->map(fn (GiftCardCategory $category) => [
            'loc'     => route('product', $category->slug),
            'lastmod' => $this->productLastModified($category),
        ]);

        $brandUrls = $brands->map(fn (MainCategory $brand) => [
            'loc'     => route('brand', $brand->slug),
            'lastmod' => $this->latest([
                $brand->updated_at,
                ...$brand->giftCardCategories->map(fn (GiftCardCategory $category) => $this->productLastModified($category))->all(),
            ]),
        ]);

        $pages = [
            ['loc' => route('home'), 'lastmod' => $this->latest($brandUrls->concat($productUrls)->pluck('lastmod')->all())],
            ['loc' => route('faq'), 'lastmod' => null],
            ['loc' => route('how-to-redeem'), 'lastmod' => null],
            ['loc' => route('contact'), 'lastmod' => null],
            ['loc' => route('about'), 'lastmod' => null],
            ['loc' => route('refund-policy'), 'lastmod' => null],
            ['loc' => route('privacy-policy'), 'lastmod' => null],
            ['loc' => route('terms'), 'lastmod' => null],
        ];

        if (ResellerProgram::fromSettings()->enabled()) {
            $pages[] = ['loc' => route('reseller'), 'lastmod' => null];
        }

        $urls = collect($pages)->concat($brandUrls)->concat($productUrls);

        return response()
            ->view('sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml');
    }

    private function productLastModified(GiftCardCategory $category): ?CarbonInterface
    {
        return $this->latest([$category->updated_at, $category->giftCards->max('updated_at')]);
    }

    /**
     * @param  array<int, CarbonInterface|null>  $dates
     */
    private function latest(array $dates): ?CarbonInterface
    {
        return collect($dates)->filter()->max();
    }
}
