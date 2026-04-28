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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'kvkk', 'distance_sales', 'membership', 'b2b_sales'
            $table->string('version')->default('1.0');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('approved_at')->useCurrent();
            $table->json('metadata')->nullable(); // For dynamic fields snapshot (e.g., buyer/seller/price snapshot)
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
