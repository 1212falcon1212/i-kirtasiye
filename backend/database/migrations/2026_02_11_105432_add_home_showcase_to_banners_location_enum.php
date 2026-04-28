<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN or ENUM; location is stored as string
            return;
        }

        DB::statement("ALTER TABLE banners MODIFY COLUMN location ENUM('home_hero','home_promo','home_middle','home_brand','home_grid','home_bottom','home_showcase','sidebar','category_top') NOT NULL DEFAULT 'home_hero'");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE banners MODIFY COLUMN location ENUM('home_hero','home_promo','home_middle','home_brand','home_grid','home_bottom','sidebar','category_top') NOT NULL DEFAULT 'home_hero'");
    }
};
