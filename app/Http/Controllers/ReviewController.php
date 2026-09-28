<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Services\SocialProfiles;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReviewController extends Controller
{
    /** Long enough that most visitors never paginate, short enough to stay fast. */
    private const PER_PAGE = 24;

    /**
     * The public wall of approved reviews.
     *
     * The homepage rail shows twelve and links here; this is the only page on
     * the site where every review has a URL of its own, which is what makes
     * them quotable somewhere other than our own marketing.
     */
    public function index()
    {
        $breakdown = $this->ratingBreakdown();
        $total     = (int) $breakdown->sum();

        return view('storefront.reviews', [
            'reviews'        => Review::approved()
                ->with('giftCardCategory:id,name,slug')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(self::PER_PAGE),
            'breakdown'      => $breakdown,
            'reviewCount'    => $total,
            'averageRating'  => $total === 0
                ? null
                : round($breakdown->keys()->sum(fn (int $rating) => $rating * $breakdown[$rating]) / $total, 1),
            'socialProfiles' => SocialProfiles::fromSettings(),
        ]);
    }

    /**
     * How many approved reviews sit at each star, 5 down to 1.
     *
     * One grouped query rather than five counts, and every star is present
     * even at zero so the breakdown does not change shape as reviews arrive.
     *
     * @return Collection<int, int>
     */
    private function ratingBreakdown(): Collection
    {
        $counts = Review::approved()
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        return collect([5, 4, 3, 2, 1])
            ->mapWithKeys(fn (int $rating) => [$rating => (int) ($counts[$rating] ?? 0)]);
    }

    public function store(Request $request, string $orderNumber)
    {
        $order = auth()->user()->orders()
            ->where('order_number', $orderNumber)
            ->whereIn('status', ['paid', 'completed'])
            ->firstOrFail();

        if ($order->reviews()->where('user_id', auth()->id())->exists()) {
            return back()->with('error', 'You have already submitted a review for this order.');
        }

        $data = $request->validate([
            'rating'     => ['required', 'integer', 'min:1', 'max:5'],
            'comment'    => ['required', 'string', 'min:10', 'max:1000'],
            'screenshot' => ['nullable', 'image', 'max:5120'],
        ]);

        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('reviews', 'public');
        }

        Review::create([
            'user_id'              => auth()->id(),
            'order_id'             => $order->id,
            'gift_card_category_id' => $this->reviewedProductId($order),
            'reviewer_name'        => auth()->user()->name,
            'rating'               => $data['rating'],
            'comment'              => $data['comment'],
            'platform'             => 'website',
            'screenshot_path'      => $screenshotPath,
            'status'               => 'pending',
            'is_verified_purchase' => true,
            'source'               => 'customer',
        ]);

        return back()->with('success', 'Thank you for your review! It will appear after approval.');
    }

    /**
     * Which product this review is about.
     *
     * Only attributed when the order bought exactly one product: a review of a
     * mixed basket says nothing specific about any single product, so guessing
     * would put words in the customer's mouth on a page they never rated.
     */
    private function reviewedProductId(Order $order): ?int
    {
        $productIds = $order->items()
            ->with('giftCard:id,category_id')
            ->get()
            ->pluck('giftCard.category_id')
            ->filter()
            ->unique();

        return $productIds->count() === 1 ? (int) $productIds->first() : null;
    }
}
