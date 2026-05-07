<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN type ENUM('sale','commission','shipping','vat','withholding','withdrawal','refund','adjustment','release') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE wallet_transactions SET type='adjustment' WHERE type='release'");
        DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN type ENUM('sale','commission','shipping','vat','withholding','withdrawal','refund','adjustment') NOT NULL");
    }
};
