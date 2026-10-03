<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per watched card per night: what the competitor was charging, what
 * the card costs us, and whether there was room between the two.
 *
 * Every check is recorded, not only the profitable ones. A card that simply
 * vanishes from the list is ambiguous -- it reads the same whether the
 * competitor turned out cheaper or the page stopped loading -- and those two
 * call for opposite responses. `is_opportunity` is what the admin screen
 * filters on; `status` is what tells them the number is trustworthy.
 *
 * The prices, the rate and the margin are all stored rather than derived at
 * read time. The rate moves, our buy price moves, and a comparison looked at
 * next month has to mean what it meant on the night it was taken.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_price_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_card_id')->constrained()->cascadeOnDelete();
            // Kept if the mapping is later deleted: the reading still happened.
            $table->foreignId('competitor_listing_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 32);
            $table->string('url', 2048)->nullable();

            $table->date('checked_on');
            $table->string('status');
            $table->string('failure_reason')->nullable();

            // Our side of the comparison, as it stood that night.
            $table->decimal('buy_price_bdt', 10, 2)->nullable();
            $table->decimal('sell_price_bdt', 10, 2)->nullable();

            // Theirs, in the currency the page quoted and in ours.
            $table->decimal('competitor_price', 10, 2)->nullable();
            $table->string('competitor_currency', 10)->nullable();
            $table->decimal('fx_rate', 12, 4)->nullable();
            $table->decimal('competitor_price_bdt', 10, 2)->nullable();

            // Stored as columns so the admin table can sort and filter on them
            // in SQL instead of loading a day's rows to rank them in PHP.
            $table->decimal('margin_bdt', 10, 2)->nullable();
            $table->decimal('margin_percent', 8, 2)->nullable();
            $table->boolean('is_opportunity')->default(false);

            $table->timestamps();

            // Re-running the command on the same day overwrites that day's
            // reading rather than stacking a second one beside it.
            $table->unique(['gift_card_id', 'provider', 'checked_on']);

            // The admin screen opens on "today, opportunities first".
            $table->index(['checked_on', 'is_opportunity']);
            $table->index(['checked_on', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_price_checks');
    }
};
