<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Change enum to include free_shipping and make discount_value nullable
        DB::statement("ALTER TABLE coupons MODIFY COLUMN discount_type ENUM('percentage', 'fixed', 'free_shipping') DEFAULT 'percentage'");
        DB::statement('ALTER TABLE coupons MODIFY COLUMN discount_value DECIMAL(10,2) NULL DEFAULT 0');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE coupons MODIFY COLUMN discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage'");
        DB::statement('ALTER TABLE coupons MODIFY COLUMN discount_value DECIMAL(10,2) NOT NULL');
    }
};
