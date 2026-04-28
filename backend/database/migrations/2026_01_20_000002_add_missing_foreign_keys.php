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
        // wallet_transactions.order_item_id icin FK ekle
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->foreign('order_item_id')
                ->references('id')
                ->on('order_items')
                ->nullOnDelete();
        });

        // users.approved_by icin self-referencing FK ekle
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['order_item_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
        });
    }
};
