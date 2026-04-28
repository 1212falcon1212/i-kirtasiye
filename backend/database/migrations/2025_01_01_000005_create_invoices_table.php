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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('seller_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('buyer_id')->nullable()->constrained('users')->onDelete('set null');

            // Fatura tipi: seller (satıcı faturası), commission (komisyon faturası), tax (vergi faturası)
            $table->enum('type', ['seller', 'commission', 'tax', 'shipping'])->default('seller');

            // Fatura durumu
            $table->enum('status', ['draft', 'pending', 'sent', 'paid', 'cancelled'])->default('draft');

            // Tutarlar
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(18);
            $table->decimal('total_amount', 12, 2)->default(0);

            // Komisyon (sadece commission tipi için)
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->decimal('commission_amount', 12, 2)->nullable();

            // Fatura bilgileri
            $table->json('seller_info')->nullable(); // Satıcı bilgileri snapshot
            $table->json('buyer_info')->nullable(); // Alıcı bilgileri snapshot
            $table->json('items')->nullable(); // Fatura kalemleri

            // ERP entegrasyonu
            $table->string('erp_provider')->nullable(); // BizimHesap, Parasut, etc.
            $table->string('erp_invoice_id')->nullable(); // Harici fatura ID
            $table->enum('erp_status', ['pending', 'synced', 'failed'])->default('pending');
            $table->text('erp_error')->nullable();
            $table->timestamp('erp_synced_at')->nullable();

            // PDF
            $table->string('pdf_path')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'type']);
            $table->index(['order_id']);
            $table->index(['status']);
            $table->index(['erp_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
