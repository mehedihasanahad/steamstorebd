<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The buyer audience groups every paid order by customer address, and the
 * admin form runs that count again on each change while a campaign is being
 * written. Without this the aggregate is a full scan of `orders` every time.
 *
 * Status first, because it is the column the query filters on; the address
 * comes second so the grouping can be fed straight from the index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'customer_email'], 'orders_status_customer_email_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_customer_email_index');
        });
    }
};
