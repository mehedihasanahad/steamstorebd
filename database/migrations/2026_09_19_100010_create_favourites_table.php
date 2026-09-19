<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A shopper's saved products. Expand-only: a new table nothing else reads, so
 * the release before this one runs unchanged against the new schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gift_card_category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // One row per shopper per product: the toggle is idempotent and a
            // double-tap can never leave two rows behind.
            $table->unique(['user_id', 'gift_card_category_id'], 'favourites_user_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favourites');
    }
};
