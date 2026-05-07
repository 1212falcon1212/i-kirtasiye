<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Performans indeksleri - v2
     *
     * products.is_active + created_at:
     *   ProductController@index sort_by=newest için (is_active filtresi + created_at sırası)
     *   Mevcut products_active_category_idx (is_active, category_id) created_at sıralamasında
     *   filesort'a düşüyor; bu composite index MySQL'in filesort'u atlamasını sağlar.
     *
     * offers.status + stock:
     *   getRecentlySold / getSeasonHighlights / getWeekProducts sorgularında
     *   WHERE status='active' AND stock>0 filtresi sık kullanılıyor. Mevcut
     *   offers_product_active_stock_price_idx (product_id, status, stock, price) product_id
     *   olmaksızın bu filtreyi verimli karşılamıyor.
     */
    public function up(): void
    {
        // products: (is_active, created_at DESC) — "newest" sort path
        Schema::table('products', function (Blueprint $table) {
            $table->index(
                ['is_active', 'created_at'],
                'products_active_created_at_idx'
            );
        });

        // offers: (status, stock) — featured sections & recently-sold path
        // (is_active ürün koşulu products tablosunda; bu index offers tarafı için)
        Schema::table('offers', function (Blueprint $table) {
            $table->index(
                ['status', 'stock'],
                'offers_status_stock_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_active_created_at_idx');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex('offers_status_stock_idx');
        });
    }
};
