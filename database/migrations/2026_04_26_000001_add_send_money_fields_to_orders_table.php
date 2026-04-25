<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','pending_review','payment_initiated','paid','processing','completed','failed','refunded') DEFAULT 'pending'");

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method', 30)->default('bkash_online')->after('status');
            $table->string('send_money_trx_id', 100)->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'send_money_trx_id']);
        });

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','payment_initiated','paid','processing','completed','failed','refunded') DEFAULT 'pending'");
    }
};
