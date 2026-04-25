<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->renameColumn('denomination_usd', 'denomination');
            $table->string('denomination_currency', 10)->default('USD')->after('denomination');
        });
    }

    public function down(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->dropColumn('denomination_currency');
            $table->renameColumn('denomination', 'denomination_usd');
        });
    }
};
