<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('main_categories', function (Blueprint $table) {
            $table->text('description')->nullable()->after('slug');
            $table->string('icon')->nullable()->after('description');
            $table->string('image')->nullable()->after('icon');
            $table->string('seo_title')->nullable()->after('is_active');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->text('seo_keywords')->nullable()->after('seo_description');
            $table->longText('seo_content')->nullable()->after('seo_keywords');
        });
    }

    public function down(): void
    {
        Schema::table('main_categories', function (Blueprint $table) {
            $table->dropColumn(['description', 'icon', 'image', 'seo_title', 'seo_description', 'seo_keywords', 'seo_content']);
        });
    }
};
