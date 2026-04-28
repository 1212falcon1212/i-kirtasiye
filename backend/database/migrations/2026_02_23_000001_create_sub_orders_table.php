<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users');
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('buyer_confirmed_at')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total_commission', 12, 2)->default(0);
            $table->decimal('total_payout', 12, 2)->default(0);
            $table->string('tracking_number', 100)->nullable();
            $table->string('shipping_provider', 50)->nullable();
            $table->string('shipping_status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'seller_id'], 'unique_order_seller');
            $table->index(['seller_id', 'status'], 'idx_seller_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_orders');
    }
};
