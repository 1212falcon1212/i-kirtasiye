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
        Schema::create('seller_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->decimal('balance', 12, 2)->default(0.00); // Çekilebilir bakiye
            $table->decimal('pending_balance', 12, 2)->default(0.00); // Bloke tutar
            $table->decimal('withdrawn_balance', 12, 2)->default(0.00); // Toplam çekilen
            $table->decimal('total_earned', 12, 2)->default(0.00); // Toplam kazanç
            $table->decimal('total_commission', 12, 2)->default(0.00); // Toplam kesilen komisyon
            $table->timestamps();

            $table->unique('seller_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_wallets');
    }
};
