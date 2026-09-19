<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Puts every existing brand under a "Gift Cards" section.
 *
 * Runs against six months of production data, so:
 *  - the column is nullable and added defensively, like the 2026_06_06
 *    main_category_id migration this mirrors;
 *  - the backfill is written with the query builder, not Eloquent, so it can
 *    never break when CatalogSection's fillable or scopes change later;
 *  - it is idempotent — only rows with no section are touched;
 *  - down() drops the column but LEAVES the seeded section row. Destroying
 *    admin-entered data on a rollback is worse than leaving an orphan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('main_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('main_categories', 'catalog_section_id')) {
                $table->unsignedBigInteger('catalog_section_id')->nullable()->after('id');
                $table->foreign('catalog_section_id')
                    ->references('id')->on('catalog_sections')
                    ->nullOnDelete();
                $table->index('catalog_section_id');
            }
        });

        $this->backfillGiftCardsSection();
    }

    public function down(): void
    {
        Schema::table('main_categories', function (Blueprint $table) {
            $table->dropForeign(['catalog_section_id']);
            $table->dropIndex(['catalog_section_id']);
            $table->dropColumn('catalog_section_id');
        });
    }

    /**
     * Every brand that exists today sells gift cards, so they all belong to
     * that section. Brands created later may be assigned in the admin.
     */
    private function backfillGiftCardsSection(): void
    {
        $sectionId = DB::table('catalog_sections')->where('slug', 'gift-cards')->value('id');

        if ($sectionId === null) {
            $sectionId = DB::table('catalog_sections')->insertGetId([
                'name'       => 'Gift Cards',
                'slug'       => 'gift-cards',
                'tagline'    => 'Steam, Google Play, App Store and more — delivered instantly',
                'icon'       => '🎁',
                'sort_order' => 0,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('main_categories')
            ->whereNull('catalog_section_id')
            ->update(['catalog_section_id' => $sectionId]);
    }
};
