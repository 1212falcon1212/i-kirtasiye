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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('offer_id')->constrained();
            $table->foreignId('seller_id')->constrained('users');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 12, 2);
            // Financial snapshot - sipariş anındaki değerler
            $table->decimal('commission_rate', 5, 2); // Kategori komisyon oranı snapshot
            $table->decimal('commission_amount', 12, 2); // Hesaplanan komisyon tutarı
            $table->decimal('seller_payout_amount', 12, 2); // Satıcıya ödenecek tutar
            $table->timestamps();

            $table->index('seller_id');
            $table->index(['order_id', 'seller_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
