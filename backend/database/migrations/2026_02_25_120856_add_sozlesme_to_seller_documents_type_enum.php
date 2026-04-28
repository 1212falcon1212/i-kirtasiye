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

        DB::statement("ALTER TABLE seller_documents MODIFY COLUMN type ENUM('ruhsat','oda_kaydi','kimlik','vergi_levhasi','imza_sirkusu','ticaret_sicili','sozlesme','diger') NOT NULL");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE seller_documents MODIFY COLUMN type ENUM('ruhsat','oda_kaydi','kimlik','vergi_levhasi','imza_sirkusu','diger') NOT NULL");
    }
};
