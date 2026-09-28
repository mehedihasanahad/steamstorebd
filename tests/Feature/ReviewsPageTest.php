<?php

/**
 * The public review wall at /reviews.
 *
 * The page exists to give reviews a crawlable home and an anchor each, and to
 * point at the outside profiles that carry ratings we cannot edit. The tests
 * that matter most here are the negative ones: it must not publish a review
 * an admin has not approved, and it must not claim a rating for the business
 * itself, which is the markup search engines discard.
 */

use App\Models\Review;
use App\Models\SiteSetting;

function approvedReview(array $overrides = []): Review
{
    return Review::create(array_merge([
        'reviewer_name' => 'Rafi',
        'rating'        => 5,
        'comment'       => 'Code arrived in under a minute, paid with bKash.',
        'platform'      => 'website',
        'status'        => 'approved',
    ], $overrides));
}

describe('the review wall', function () {
    it('publishes approved reviews and nothing else', function () {
        approvedReview(['comment' => 'Fast and genuine, will buy again.']);
        approvedReview(['comment' => 'Still waiting on approval.', 'status' => 'pending']);
        approvedReview(['comment' => 'This one was rejected.', 'status' => 'rejected']);

        $this->get('/reviews')
            ->assertSuccessful()
            ->assertSee('Fast and genuine, will buy again.')
            ->assertDontSee('Still waiting on approval.')
            ->assertDontSee('This one was rejected.');
    });

    it('gives every review an anchor of its own', function () {
        $review = approvedReview();

        $this->get('/reviews')
            ->assertSuccessful()
            ->assertSee('id="review-' . $review->id . '"', false)
            ->assertSee(route('reviews') . '#review-' . $review->id, false);
    });

    it('averages only the approved ratings', function () {
        approvedReview(['rating' => 5]);
        approvedReview(['rating' => 4]);
        approvedReview(['rating' => 1, 'status' => 'pending']);

        $this->get('/reviews')
            ->assertSuccessful()
            ->assertSee('4.5')
            ->assertSee('2 approved reviews');
    });

    it('names the product a review was left for', function () {
        $product = seoProduct(seoBrand());
        approvedReview(['gift_card_category_id' => $product->id]);

        $this->get('/reviews')
            ->assertSuccessful()
            ->assertSee(route('product', 'steam-wallet'), false)
            ->assertSee('Steam Wallet');
    });

    it('offers an empty state rather than a bare page', function () {
        $this->get('/reviews')
            ->assertSuccessful()
            ->assertSee('No reviews published yet')
            ->assertDontSee('approved review');
    });

    it('paginates once past a full page', function () {
        foreach (range(1, 26) as $i) {
            approvedReview(['comment' => "Order number {$i} landed instantly."]);
        }

        $this->get('/reviews')
            ->assertSuccessful()
            ->assertSee('?page=2', false);
    });
});

describe('review wall structured data', function () {
    it('publishes each review as a list item tied to its product', function () {
        $product = seoProduct(seoBrand());
        approvedReview(['gift_card_category_id' => $product->id, 'rating' => 4]);

        $this->get('/reviews')
            ->assertSuccessful()
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"@type":"ItemList"', false)
            ->assertSee('"@type":"Review"', false)
            ->assertSee('"ratingValue":"4"', false)
            ->assertSee('"itemReviewed"', false)
            ->assertSee(route('product', 'steam-wallet'), false);
    });

    it('never claims an aggregate rating for the business itself', function () {
        approvedReview();

        // Self-serving review markup is ignored by search engines and risks a
        // structured-data warning. The page shows the average to humans and
        // leaves the claim out of the markup.
        $this->get('/reviews')
            ->assertSuccessful()
            ->assertDontSee('AggregateRating', false);
    });

    it('leaves itemReviewed off a review with no product', function () {
        approvedReview();

        $this->get('/reviews')
            ->assertSuccessful()
            ->assertSee('"@type":"Review"', false)
            ->assertDontSee('"itemReviewed"', false);
    });
});

describe('outside profiles', function () {
    it('links the profiles configured in site settings', function () {
        SiteSetting::set('social_google_business_url', 'https://g.page/r/steamstorebd', 'social');
        SiteSetting::set('social_trustpilot_url', 'https://www.trustpilot.com/review/steamstorebd.com', 'social');
        approvedReview();

        $this->get('/reviews')
            ->assertSuccessful()
            ->assertSee('Reviews we cannot edit live on our public profiles')
            ->assertSee('https://g.page/r/steamstorebd', false)
            ->assertSee('https://www.trustpilot.com/review/steamstorebd.com', false);
    });

    it('says nothing about outside profiles when none are set', function () {
        approvedReview();

        $this->get('/reviews')
            ->assertSuccessful()
            ->assertDontSee('Reviews we cannot edit live on our public profiles');
    });
});
