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
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            // Kargo firması: aras, yurtici, mng, sendeo, hepsijet, ptt, surat, kolaygelsin
            $table->string('provider');
            // Desi aralığı
            $table->decimal('min_desi', 8, 2)->default(0);
            $table->decimal('max_desi', 8, 2);
            // Fiyat (TL)
            $table->decimal('price', 10, 2);
            // İl bazlı fark (opsiyonel)
            $table->string('region')->nullable(); // istanbul, marmara, anadolu, vs
            $table->decimal('region_price', 10, 2)->nullable();
            // Aktiflik
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['provider', 'is_active']);
            $table->index(['min_desi', 'max_desi']);
        });

        // Ücretsiz kargo kuralları tablosu
        Schema::create('free_shipping_rules', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->nullable(); // null = tümü için geçerli
            $table->decimal('min_order_amount', 10, 2); // minimum sipariş tutarı
            $table->decimal('max_desi', 8, 2)->nullable(); // maksimum desi limiti
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('free_shipping_rules');
        Schema::dropIfExists('shipping_rates');
    }
};
