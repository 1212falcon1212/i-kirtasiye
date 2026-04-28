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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('offer_id')->constrained();
            $table->foreignId('seller_id')->constrained('users');
            $table->integer('quantity')->default(1);
            $table->decimal('price_at_addition', 10, 2); // Sepete eklendiği andaki fiyat
            $table->timestamps();

            $table->unique(['cart_id', 'offer_id']); // Aynı teklif sepette 1 kere olabilir
            $table->index('seller_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
