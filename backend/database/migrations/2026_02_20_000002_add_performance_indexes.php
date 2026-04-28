<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Offers: covers activeOffers scope (status=active, stock>0) + product lookup + price sort
        Schema::table('offers', function (Blueprint $table) {
            $table->index(
                ['product_id', 'status', 'stock', 'price'],
                'offers_product_active_stock_price_idx'
            );
        });

        // Products: covers Product::active() scope + category filtering
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'category_id'], 'products_active_category_idx');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex('offers_product_active_stock_price_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_active_category_idx');
        });
    }
};
