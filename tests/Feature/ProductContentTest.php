<?php

use App\Models\GiftCardCategory;
use App\Models\Review;

describe('instructions', function () {
    it('uses its own instructions when set', function () {
        $brand   = seoBrand(['how_to_redeem' => 'Brand steps']);
        $product = seoProduct($brand, ['instructions' => 'Product steps']);

        expect($product->redemptionInstructions())->toBe('Product steps');
    });

    it('falls back to the brand steps when blank', function () {
        $brand   = seoBrand(['how_to_redeem' => 'Brand steps']);
        $product = seoProduct($brand);

        expect($product->redemptionInstructions())->toBe('Brand steps');
    });

    it('reports nothing when neither is written', function () {
        expect(seoProduct(seoBrand())->redemptionInstructions())->toBeNull();
    });

    it('reports nothing when the product has no brand at all', function () {
        expect(seoProduct()->redemptionInstructions())->toBeNull();
    });
});

describe('faq', function () {
    it('is empty by default', function () {
        expect(seoProduct(seoBrand())->faqEntries())->toBe([]);
    });

    it('stores and reads back its entries', function () {
        $product = seoProduct(seoBrand(), [
            'faq' => [
                ['question' => 'Does it expire?', 'answer' => 'No.'],
                ['question' => 'Which region?', 'answer' => 'Global.'],
            ],
        ]);

        $entries = GiftCardCategory::find($product->id)->faqEntries();

        expect($entries)->toHaveCount(2)
            ->and($entries[0]['question'])->toBe('Does it expire?');
    });
});

describe('ratings', function () {
    it('reports no rating when nobody has reviewed the product', function () {
        $product = seoProduct(seoBrand());

        expect($product->averageRating())->toBeNull()
            ->and($product->reviewCount())->toBe(0);
    });

    it('averages approved reviews only', function () {
        $product = seoProduct(seoBrand());

        Review::create(['gift_card_category_id' => $product->id, 'rating' => 5, 'comment' => 'Great', 'status' => 'approved']);
        Review::create(['gift_card_category_id' => $product->id, 'rating' => 4, 'comment' => 'Good', 'status' => 'approved']);
        Review::create(['gift_card_category_id' => $product->id, 'rating' => 1, 'comment' => 'Pending one', 'status' => 'pending']);

        expect($product->averageRating())->toBe(4.5)
            ->and($product->reviewCount())->toBe(2);
    });

    it('rounds the average to one decimal', function () {
        $product = seoProduct(seoBrand());

        foreach ([5, 4, 4] as $rating) {
            Review::create(['gift_card_category_id' => $product->id, 'rating' => $rating, 'comment' => 'x', 'status' => 'approved']);
        }

        expect($product->averageRating())->toBe(4.3);
    });

    it('never counts another product reviews', function () {
        $brand = seoBrand();
        $a     = seoProduct($brand, ['slug' => 'a']);
        $b     = seoProduct($brand, ['slug' => 'b']);

        Review::create(['gift_card_category_id' => $a->id, 'rating' => 5, 'comment' => 'x', 'status' => 'approved']);

        expect($b->averageRating())->toBeNull();
    });
});

describe('existing reviews', function () {
    /**
     * The regression that matters: six months of reviews predate this column
     * and must keep rendering exactly where they do now.
     */
    it('keeps a review with no product valid and approved', function () {
        Review::create(['rating' => 5, 'comment' => 'Old review', 'status' => 'approved']);

        expect(Review::approved()->count())->toBe(1)
            ->and(Review::approved()->first()->gift_card_category_id)->toBeNull();
    });

    it('still shows product-less reviews on the homepage', function () {
        Review::create(['rating' => 5, 'comment' => 'Legacy testimonial', 'status' => 'approved']);

        $this->get(route('home'))->assertSuccessful()->assertSee('Legacy testimonial');
    });
});
