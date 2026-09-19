<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Merchandising: struck-through pricing, featured products and per-card
 * purchase limits.
 *
 * compare_at_price_bdt is presentational only. It must never touch
 * buy_price_bdt, which drives the margin reporting in GiftCardResource.
 * A deal is derived from the two prices rather than carried as its own flag,
 * so there is one source of truth and nothing to forget to switch off.
 *
 * max_quantity defaults to 10 because that is the cap CheckoutController
 * currently hardcodes, so every existing row keeps behaving identically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->decimal('compare_at_price_bdt', 10, 2)->nullable()->after('price_bdt');
            $table->unsignedInteger('min_quantity')->default(1)->after('compare_at_price_bdt');
            $table->unsignedInteger('max_quantity')->default(10)->after('min_quantity');
        });

        Schema::table('gift_card_categories', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->integer('featured_sort')->default(0)->after('is_featured');

            $table->index(['is_featured', 'featured_sort']);
        });
    }

    public function down(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->dropColumn(['compare_at_price_bdt', 'min_quantity', 'max_quantity']);
        });

        Schema::table('gift_card_categories', function (Blueprint $table) {
            $table->dropIndex(['is_featured', 'featured_sort']);
            $table->dropColumn(['is_featured', 'featured_sort']);
        });
    }
};
