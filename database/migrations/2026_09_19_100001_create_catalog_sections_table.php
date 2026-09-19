<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The top level of the catalog, above brands: Gift Cards, Software,
 * Subscriptions, Game Top-Up.
 *
 * A section is not a brand. It owns presentation (tagline, accent colour, nav
 * icon) where a brand owns redemption instructions and a portrait cover, which
 * is why this is its own table rather than a parent_id on main_categories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->string('accent_color', 7)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_content')->nullable();
            $table->timestamps();

            // Every storefront read filters on is_active and orders by
            // sort_order, so index the pair rather than either alone.
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_sections');
    }
};
