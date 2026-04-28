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
        Schema::create('user_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('erp_type'); // parasut, entegra, sentos, bizimhesap
            $table->text('api_key')->nullable(); // Encrypted
            $table->text('api_secret')->nullable(); // Encrypted
            $table->text('app_id')->nullable(); // Encrypted
            $table->json('extra_params')->nullable(); // Encrypted fields inside or plain
            $table->timestamp('last_sync_at')->nullable();
            $table->string('status')->default('active'); // active, inactive, error
            $table->text('error_message')->nullable();
            $table->timestamps();

            // A user can have only one integration of a specific type
            $table->unique(['user_id', 'erp_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_integrations');
    }
};
