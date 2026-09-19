<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the buyer must tell us before we can fulfil this product: a Player ID
 * for a game top-up, an account e-mail for a subscription.
 *
 * Lives on the product rather than the card because the form is rendered once
 * per product page and is identical across its denominations — "PUBG UC" asks
 * for a Player ID whether you buy 60 UC or 8100 UC.
 *
 * Null means "ask for nothing", which is every existing row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_card_categories', function (Blueprint $table) {
            $table->json('buyer_input_fields')->nullable()->after('long_description');
        });
    }

    public function down(): void
    {
        Schema::table('gift_card_categories', function (Blueprint $table) {
            $table->dropColumn('buyer_input_fields');
        });
    }
};
