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
        Schema::table('products', function (Blueprint $table) {
            $table->string('approval_status')->default('approved'); // pending, approved, rejected
            $table->string('source')->default('system'); // system, manual, erp
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['created_by_id']);
            $table->dropColumn(['approval_status', 'source', 'created_by_id']);
        });
    }
};
