<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('seller_bank_accounts', function (Blueprint $table) {
            $table->string('tax_id', 11)->nullable()->after('swift_code');
            $table->string('tax_office', 100)->nullable()->after('tax_id');
            $table->string('kep_address', 255)->nullable()->after('tax_office');
            $table->string('mersis_number', 20)->nullable()->after('kep_address');
            $table->string('phone', 20)->nullable()->after('mersis_number');
        });
    }

    public function down(): void
    {
        Schema::table('seller_bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['tax_id', 'tax_office', 'kep_address', 'mersis_number', 'phone']);
        });
    }
};
