<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The payment fee a buyer pays on top of the listed price.
 *
 * A shelf price is not what leaving the shop costs. G2A adds a per-order
 * payment fee, so comparing our landed cost against their listed price
 * flattered them by that amount on every single card -- small in dollars,
 * decisive on a card whose whole margin is a few taka.
 *
 * Stored per reading, in the currency the page quoted, for the same reason
 * the rate is: the fee is a setting that moves, and a comparison taken last
 * month has to keep meaning what it meant then.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitor_price_checks', function (Blueprint $table) {
            $table->decimal('competitor_fee', 10, 2)->nullable()->after('competitor_currency');
        });
    }

    public function down(): void
    {
        Schema::table('competitor_price_checks', function (Blueprint $table) {
            $table->dropColumn('competitor_fee');
        });
    }
};
