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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shipping_cost', 10, 2)->default(0)->after('total_amount');
            $table->string('shipping_provider')->nullable()->after('shipping_cost');
            $table->string('tracking_number')->nullable()->after('shipping_provider');
            $table->enum('shipping_status', [
                'pending',      // Bekliyor
                'processing',   // Hazırlanıyor
                'shipped',      // Kargoya verildi
                'in_transit',   // Yolda
                'out_for_delivery', // Dağıtımda
                'delivered',    // Teslim edildi
                'returned',     // İade edildi
                'failed',       // Başarısız
            ])->default('pending')->after('tracking_number');
            $table->string('shipping_label_url')->nullable()->after('shipping_status');
            $table->timestamp('shipped_at')->nullable()->after('shipping_label_url');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_cost',
                'shipping_provider',
                'tracking_number',
                'shipping_status',
                'shipping_label_url',
                'shipped_at',
                'delivered_at',
            ]);
        });
    }
};
