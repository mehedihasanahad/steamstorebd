<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a review be attributed to the product it was left for, so the product
 * page can show a rating.
 *
 * Nullable is doing real work: every review written in the six months before
 * this column existed stays valid, keeps its place in the homepage testimonial
 * block, and simply carries no product.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('gift_card_category_id')
                ->nullable()
                ->after('order_id')
                ->constrained('gift_card_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['gift_card_category_id']);
            $table->dropColumn('gift_card_category_id');
        });
    }
};
