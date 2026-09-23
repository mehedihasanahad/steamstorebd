<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How a section draws its brands on the homepage: one scrolling row, or a grid
 * that wraps.
 *
 * Presentation already belongs to the section rather than to the view — the
 * tagline, the accent colour and the nav icon are all stored here — and which
 * of the two shapes suits a section depends on how many brands it holds, which
 * only the person filling the catalog knows. A section with four brands looks
 * half-empty in a rail; one with twenty is a wall as a grid.
 *
 * Defaults to 'slider' because that is what every existing section renders as
 * today, so the column changes nothing until someone chooses otherwise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_sections', function (Blueprint $table) {
            $table->string('display_mode', 10)->default('slider')->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_sections', function (Blueprint $table) {
            $table->dropColumn('display_mode');
        });
    }
};
