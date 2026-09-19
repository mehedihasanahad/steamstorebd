<?php

namespace Database\Seeders\Demo;

use App\Models\GiftCardCategory;
use App\Models\Review;
use Illuminate\Database\Seeder;

/**
 * Reviews for the homepage testimonial rail and for the product rating block.
 *
 * Two deliberate shapes: reviews attributed to a product, which is what makes
 * a rating appear on that product page, and an unattributed one, which is what
 * every review written before reviews could name a product looks like.
 */
class ReviewDemoSeeder extends Seeder
{
    public function run(): void
    {
        $steamHkd = GiftCardCategory::where('slug', 'steam-wallet-hkd')->first();
        $pubgUc   = GiftCardCategory::where('slug', 'pubg-uc')->first();

        $reviews = [
            ['Rahim Uddin', 5, 'Code arrived in under two minutes. Smooth from start to finish.', 'whatsapp', true, $steamHkd?->id, 0],
            ['Karim Ahmed', 4, 'Good price, and support answered my question about the region straight away.', 'website', false, $steamHkd?->id, 1],
            ['Nadia Islam', 5, 'Third time buying here. Never had a problem with a code.', 'messenger', true, $steamHkd?->id, 2],
            ['Tanvir Hasan', 5, 'UC was on my account before I finished making tea. Will use again.', 'whatsapp', true, $pubgUc?->id, 3],
            ['Shakib Rahman', 4, 'Cheaper than the shop near me and I did not have to leave the house.', 'website', true, $pubgUc?->id, 4],

            // No product: every review written before reviews could be
            // attributed looks like this, and must keep appearing on the
            // homepage exactly as it did.
            ['Ayesha Khan', 5, 'Been using Steam Store BD since last year. Always genuine codes.', 'website', false, null, 5],
        ];

        foreach ($reviews as [$name, $rating, $comment, $platform, $verified, $productId, $sort]) {
            Review::updateOrCreate(['comment' => $comment], [
                'reviewer_name'         => $name,
                'rating'                => $rating,
                'platform'              => $platform,
                'status'                => 'approved',
                'is_verified_purchase'  => $verified,
                'gift_card_category_id' => $productId,
                'sort_order'            => $sort,
            ]);
        }

        // One pending review, so "approved only" is a rule the demo can prove
        // rather than a claim nothing contradicts.
        Review::updateOrCreate(
            ['comment' => 'Waiting on approval — this must never appear on the storefront.'],
            ['reviewer_name' => 'Pending Person', 'rating' => 1, 'status' => 'pending', 'platform' => 'website'],
        );
    }
}
