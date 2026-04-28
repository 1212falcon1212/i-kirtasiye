<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Update ENUM to include all location options (MySQL only)
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE banners MODIFY COLUMN location ENUM('home_hero', 'home_promo', 'home_middle', 'home_brand', 'home_grid', 'home_bottom', 'sidebar', 'category_top') NOT NULL DEFAULT 'home_hero'");
        }
        // SQLite: location is already a string type, no modification needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE banners MODIFY COLUMN location ENUM('home_hero', 'home_middle', 'sidebar', 'category_top') NOT NULL DEFAULT 'home_hero'");
        }
    }
};
