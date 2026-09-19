<?php

namespace App\Http\Controllers;

use App\Models\CatalogSection;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\MainCategory;
use App\Models\SlugRedirect;
use App\Services\CatalogBrowser;
use App\Services\PaymentMethods;
use App\Services\ReferralService;
use App\Services\StorefrontCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Browsing the catalog: sections, brands and products.
 *
 * Sections and brands render the same listing view from the same browser
 * service and differ only in scope and heading, which is what keeps the two
 * pages from drifting apart as filters are added to one of them.
 */
class CatalogController extends Controller
{
    public function __construct(
        private CatalogBrowser $browser,
        private StorefrontCatalog $catalog,
    ) {}

    /** /category/{slug} — every product in one vertical. */
    public function section(Request $request, string $sectionSlug)
    {
        $section = CatalogSection::where('slug', $sectionSlug)->where('is_active', true)->first();

        if ($section === null) {
            $moved = SlugRedirect::findModel(CatalogSection::class, $sectionSlug);
            abort_unless($moved?->is_active, 404);

            return redirect()->route('category', $moved->slug, 301);
        }

        return view('storefront.catalog', $this->listing($request, section: $section));
    }

    /** /brand/{slug} — every product from one brand. */
    public function brand(Request $request, string $mainCategorySlug)
    {
        $brand = MainCategory::with('catalogSection')
            ->where('slug', $mainCategorySlug)
            ->where('is_active', true)
            ->first();

        if ($brand === null) {
            $moved = SlugRedirect::findModel(MainCategory::class, $mainCategorySlug);
            abort_unless($moved?->is_active, 404);

            return redirect()->route('brand', $moved->slug, 301);
        }

        return view('storefront.catalog', $this->listing($request, brand: $brand));
    }

    /** /product/{slug} — one product, its denominations and its siblings. */
    public function product(string $categorySlug)
    {
        $category = GiftCardCategory::with('mainCategory.catalogSection')
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

        return view('storefront.product', [
            'category'            => $category,
            'denominations'       => $denominations,
            'relatedCategories'   => $relatedCategories,
            'regionalSiblings'    => $category->regionalSiblings(),
            'averageRating'       => $category->averageRating(),
            'reviewCount'         => $category->reviewCount(),
            'paymentMethodNames'  => PaymentMethods::walletNames(),
            'referralSettings'    => app(ReferralService::class)->getSettings(),
            'referralCode'        => Auth::check() ? Auth::user()->referral_code : null,
        ]);
    }

    /** A card URL names a denomination; the page that sells it is the product. */
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

    /**
     * Everything the shared listing view needs, whichever route asked for it.
     *
     * @return array<string, mixed>
     */
    private function listing(Request $request, ?CatalogSection $section = null, ?MainCategory $brand = null): array
    {
        $region = $request->query('region');
        $sort   = CatalogBrowser::normaliseSort($request->query('sort'));

        return [
            'section'       => $section,
            'brand'         => $brand,
            'heading'       => $brand?->name ?? $section->name,
            'tagline'       => $brand?->description ?? $section?->tagline,
            'products'      => $this->browser->products($section, $brand, $region, $sort),
            'regionFilters' => $this->browser->regionFilters($section, $brand),
            'featured'      => $this->browser->featured(),
            'railSections'  => $this->browser->rail($this->catalog),
            'activeRegion'  => $region,
            'activeSort'    => $sort,
            'paymentMethodNames' => PaymentMethods::walletNames(),
        ];
    }
}
