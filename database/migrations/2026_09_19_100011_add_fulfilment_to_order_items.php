<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The order side of manual fulfilment.
 *
 * `fulfilment_status` is NULLABLE with no default on purpose. Null means "this
 * line is delivered from the code pool", which is what every row written in
 * the six months before this column existed is — and their real state is
 * already carried by their reserved or sold codes. Defaulting the column to
 * any value would either claim an unpaid order had been fulfilled or claim a
 * delivered one had not. Only lines that actually need an admin to act carry
 * a value here.
 *
 * `delivered_payload` holds account credentials for subscription lines, so it
 * is encrypted at rest through the model cast. That makes APP_KEY part of the
 * backup set: losing it makes these rows unrecoverable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('fulfilment_status', 20)->nullable()->after('subtotal_bdt');
            $table->json('buyer_inputs')->nullable()->after('fulfilment_status');
            $table->text('delivered_payload')->nullable()->after('buyer_inputs');
            $table->timestamp('fulfilled_at')->nullable()->after('delivered_payload');

            $table->index('fulfilment_status');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['fulfilment_status']);
            $table->dropColumn(['fulfilment_status', 'buyer_inputs', 'delivered_payload', 'fulfilled_at']);
        });
    }
};
