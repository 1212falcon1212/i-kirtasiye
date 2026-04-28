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
        // order_items tablosuna product_id ve offer_id indeksi
        Schema::table('order_items', function (Blueprint $table) {
            $table->index('product_id', 'order_items_product_id_index');
            $table->index('offer_id', 'order_items_offer_id_index');
        });

        // cart_items tablosuna product_id indeksi
        Schema::table('cart_items', function (Blueprint $table) {
            $table->index('product_id', 'cart_items_product_id_index');
        });

        // user_addresses tablosuna user_id indeksi
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->index('user_id', 'user_addresses_user_id_index');
        });

        // invoices tablosuna buyer_id indeksi
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('buyer_id', 'invoices_buyer_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_product_id_index');
            $table->dropIndex('order_items_offer_id_index');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('cart_items_product_id_index');
        });

        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropIndex('user_addresses_user_id_index');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_buyer_id_index');
        });
    }
};
