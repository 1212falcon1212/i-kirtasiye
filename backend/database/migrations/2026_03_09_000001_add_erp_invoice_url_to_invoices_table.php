<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('erp_invoice_url')->nullable()->after('erp_invoice_id');
            $table->foreignId('sub_order_id')->nullable()->after('order_id')->constrained('sub_orders')->onDelete('set null');

            $table->index('sub_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['sub_order_id']);
            $table->dropIndex(['sub_order_id']);
            $table->dropColumn(['erp_invoice_url', 'sub_order_id']);
        });
    }
};
