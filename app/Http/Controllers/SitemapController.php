<?php

namespace App\Http\Controllers;

use App\Models\CatalogSection;
use App\Models\GiftCardCategory;
use App\Models\MainCategory;
use App\Models\Review;
use App\Services\ExclusiveOffers;
use App\Services\PaymentMethods;
use App\Services\ResellerProgram;
use App\Services\StorefrontCatalog;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

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

        // Section pages are only advertised when they actually have something
        // to sell, for the same reason a hidden product is left out: never
        // point a crawler at a page a shopper would find empty.
        $sectionUrls = $catalog->sectionsWithBrands()->map(fn (CatalogSection $section) => [
            'loc'     => route('category', $section->slug),
            'lastmod' => $this->latest([
                $section->updated_at,
                ...$section->mainCategories
                    ->flatMap(fn (MainCategory $brand) => $brand->giftCardCategories)
                    ->map(fn (GiftCardCategory $category) => $this->productLastModified($category))
                    ->all(),
            ]),
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

        // Same rule as a section page: advertise the review wall only once it
        // has something on it. An empty page is a wasted crawl and a worse
        // first impression than no page at all.
        if ($latestReview = Review::approved()->max('created_at')) {
            $pages[] = ['loc' => route('reviews'), 'lastmod' => Carbon::parse($latestReview)];
        }

        if (ResellerProgram::fromSettings()->enabled()) {
            $pages[] = ['loc' => route('reseller'), 'lastmod' => null];
        }

        // Only while the programme is on: the page 404s otherwise, and there
        // is no point pointing a crawler at a page that is not there.
        if (ExclusiveOffers::fromSettings()->enabled()) {
            $pages[] = ['loc' => route('offers'), 'lastmod' => null];
        }

        $urls = collect($pages)->concat($sectionUrls)->concat($brandUrls)->concat($productUrls);

        return response()
            ->view('sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml');
    }

    /**
     * /llms.txt — the shop stated once, in plain markdown, for the assistants
     * that answer "where do I buy a Steam card in Bangladesh".
     *
     * A crawler that renders no JavaScript and keeps no session still gets the
     * whole catalogue here: what is sold, what it costs, how it is paid for
     * and how fast it arrives. It is generated rather than written so it can
     * never drift from what is actually in stock.
     */
    public function llms(StorefrontCatalog $catalog)
    {
        $wallets = PaymentMethods::walletNames() ?: ['bKash', 'Nagad', 'Rocket'];

        $lines = [
            '# Steam Store BD',
            '',
            '> Bangladesh-based digital goods store selling gift cards, game top-ups, '
                .'game keys and subscriptions. Paid for with '.Arr::join($wallets, ', ', ' or ')
                .'. Every code is delivered by e-mail within minutes of payment.',
            '',
            '- Country: Bangladesh',
            '- Currency: BDT (৳)',
            '- Payment methods: '.Arr::join($wallets, ', ', ' and '),
            '- Delivery: digital code by e-mail and in the account area, usually within minutes',
            '- Returns: codes are non-refundable once revealed ('.route('refund-policy').')',
            '',
        ];

        // Grouped off brands() rather than sectionsWithBrands(), for the same
        // reason the sitemap does: a brand that has not been filed under a
        // section is still being sold, and listing only the filed ones would
        // quietly drop it from the one file an assistant reads.
        $grouped = $catalog->brands()->groupBy(
            fn (MainCategory $brand) => $brand->catalogSection?->name ?? 'Gift cards'
        );

        foreach ($grouped as $sectionName => $brands) {
            $lines[] = '## '.$sectionName;
            $lines[] = '';
            foreach ($brands as $brand) {
                foreach ($brand->giftCardCategories as $category) {
                    $lines[] = '- '.$this->llmsProductLine($category);
                }
            }
            $lines[] = '';
        }

        if (($unbranded = $catalog->unbrandedCategories())->isNotEmpty()) {
            $lines[] = '## Other products';
            $lines[] = '';
            foreach ($unbranded as $category) {
                $lines[] = '- '.$this->llmsProductLine($category);
            }
            $lines[] = '';
        }

        $lines[] = '## Help';
        $lines[] = '';
        foreach ([
            'Frequently asked questions' => route('faq'),
            'How to redeem a code'       => route('how-to-redeem'),
            'Customer reviews'           => route('reviews'),
            'Refund policy'              => route('refund-policy'),
            'Terms of service'           => route('terms'),
            'Contact'                    => route('contact'),
        ] as $label => $url) {
            $lines[] = '- ['.$label.']('.$url.')';
        }

        return response(implode(PHP_EOL, $lines), 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }

    /**
     * One catalogue line: name, link, price floor and whether it can be bought
     * right now — the four things an answer actually needs.
     */
    private function llmsProductLine(GiftCardCategory $category): string
    {
        $inStock = $category->giftCards->where('is_active', true);
        $from    = $inStock->min('price_bdt');

        return sprintf(
            '[%s](%s)%s%s',
            $category->name,
            route('product', $category->slug),
            $from ? ' — from ৳'.number_format((float) $from) : '',
            $inStock->sum('stock_count') > 0 ? '' : ' (out of stock)'
        );
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
