<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Pazaryeri hizmet bedeli (%0.89)
            $table->decimal('marketplace_fee', 10, 2)->default(0)->after('commission_amount');
            // Stopaj (%1)
            $table->decimal('withholding_tax', 10, 2)->default(0)->after('marketplace_fee');
            // Kargo payı (opsiyonel)
            $table->decimal('shipping_cost_share', 10, 2)->default(0)->after('withholding_tax');
            // Net satıcı tutarı (tüm kesintiler düşüldükten sonra)
            $table->decimal('net_seller_amount', 10, 2)->default(0)->after('shipping_cost_share');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'marketplace_fee',
                'withholding_tax',
                'shipping_cost_share',
                'net_seller_amount',
            ]);
        });
    }
};
