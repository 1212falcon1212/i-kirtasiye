<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satıcı bazlı kargo override alanları.
     *
     * default_shipping_fee: Satıcının kendi sabit kargo ücreti. NULL ise
     *   global setting (`shipping.flat_rate`) kullanılır.
     * free_shipping_threshold: Satıcının kendi ücretsiz kargo eşiği.
     *   NULL ise global setting (`shipping.free_threshold`) kullanılır.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('default_shipping_fee', 10, 2)
                ->nullable()
                ->after('seller_review_count')
                ->comment('Satıcının kendi sabit kargo ücreti. NULL ise global default.');

            $table->decimal('free_shipping_threshold', 10, 2)
                ->nullable()
                ->after('default_shipping_fee')
                ->comment('Satıcının kendi ücretsiz kargo eşiği. NULL ise global default.');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['default_shipping_fee', 'free_shipping_threshold']);
        });
    }
};
