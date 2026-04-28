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
        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('bank_account_id')->nullable()->constrained('seller_bank_accounts')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('status', [
                'pending',    // Beklemede
                'approved',   // Onaylandı
                'rejected',   // Reddedildi
                'processing', // İşleniyor
                'completed',  // Tamamlandı
                'failed',     // Başarısız
            ])->default('pending');
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_requests');
    }
};
