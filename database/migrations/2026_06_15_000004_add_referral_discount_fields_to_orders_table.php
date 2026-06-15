<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('referral_code_used', 10)->nullable()->after('ip_address');
            $table->decimal('referral_discount_bdt', 10, 2)->default(0)->after('referral_code_used');
            $table->decimal('wallet_discount_bdt', 10, 2)->default(0)->after('referral_discount_bdt');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['referral_code_used', 'referral_discount_bdt', 'wallet_discount_bdt']);
        });
    }
};
