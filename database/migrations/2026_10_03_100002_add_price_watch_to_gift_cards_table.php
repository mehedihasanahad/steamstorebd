<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The switch that decides whether the nightly price check looks at a card.
 *
 * On by default, so pasting a competitor URL is all it takes to start
 * watching -- a card with no URL is skipped regardless. Turning it off is how
 * a card is paused without losing the URL that was already researched for it,
 * which matters for cards we deliberately price against something other than
 * the market: bundles, loss leaders, and anything bought on a fixed contract.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->boolean('price_watch_enabled')->default(true)->after('buy_price_bdt');
        });
    }

    public function down(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->dropColumn('price_watch_enabled');
        });
    }
};
