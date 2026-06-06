<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'pending_review', 'payment_initiated', 'paid', 'processing', 'completed', 'failed', 'refunded', 'cancelled')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'pending_review', 'payment_initiated', 'paid', 'processing', 'completed', 'failed', 'refunded')");
    }
};
