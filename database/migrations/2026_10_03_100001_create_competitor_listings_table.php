<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which page on a competitor's site sells the same thing we do.
 *
 * The mapping is typed in by an admin rather than guessed from the card's
 * name. A name search cannot tell "Steam Wallet $10 US" from "Steam Wallet
 * EUR 10 EU", and the whole point of this feature is to price against the
 * right product -- a wrong match is worse than no match, because it looks
 * like an answer.
 *
 * A card may carry one listing per provider, so a second price source can be
 * added later without touching the cards themselves. Whether a card is
 * watched at all is a flag on the card, not here: see the migration that adds
 * `price_watch_enabled` to `gift_cards`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_card_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32)->default('g2a');
            $table->string('url', 2048);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            // One page per card per provider. Two rows for the same provider
            // would mean two competing answers for one card every night.
            $table->unique(['gift_card_id', 'provider']);

            // The nightly run selects listings by provider.
            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_listings');
    }
};
