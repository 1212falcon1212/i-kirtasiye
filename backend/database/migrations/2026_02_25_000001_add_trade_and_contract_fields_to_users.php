<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('trade_name', 255)->nullable()->after('city');
            $table->string('kep_address', 255)->nullable()->after('trade_name');
            $table->string('mersis_no', 20)->nullable()->after('kep_address');
            $table->string('tax_number', 20)->nullable()->after('mersis_no');
            $table->string('tax_office', 100)->nullable()->after('tax_number');

            $table->timestamp('contract_signed_at')->nullable()->after('approved_by');
            $table->string('contract_ip', 45)->nullable()->after('contract_signed_at');
            $table->text('contract_user_agent')->nullable()->after('contract_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'trade_name',
                'kep_address',
                'mersis_no',
                'tax_number',
                'tax_office',
                'contract_signed_at',
                'contract_ip',
                'contract_user_agent',
            ]);
        });
    }
};
