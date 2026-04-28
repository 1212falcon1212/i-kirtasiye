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
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // invoice, shipping, payment, auth
            $table->string('provider', 50)->nullable(); // bizimhesap, aras, yurtici, etc.
            $table->string('action', 100); // create_invoice, create_shipment, track, etc.
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->integer('response_code')->nullable();
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->text('error')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['provider', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
