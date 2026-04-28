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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('seller_wallets')->onDelete('cascade');
            $table->enum('type', [
                'sale',           // Satış geliri
                'commission',     // Komisyon kesintisi
                'shipping',       // Kargo masrafı
                'vat',            // KDV
                'withholding',    // Stopaj
                'withdrawal',     // Para çekme
                'refund',         // İade
                'adjustment',     // Manuel düzeltme
            ]);
            $table->decimal('amount', 12, 2);
            $table->enum('direction', ['credit', 'debit']); // credit = artış, debit = azalış
            $table->enum('balance_type', ['pending', 'available']); // Hangi bakiyeyi etkiliyor
            $table->string('description')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('order_item_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
            $table->index(['order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
