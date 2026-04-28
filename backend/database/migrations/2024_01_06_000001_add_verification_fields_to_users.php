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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('is_verified');
            $table->text('rejection_reason')->nullable()->after('verification_status');
            $table->json('documents')->nullable()->after('rejection_reason');
            $table->timestamp('approved_at')->nullable()->after('documents');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'verification_status',
                'rejection_reason',
                'documents',
                'approved_at',
                'approved_by',
            ]);
        });
    }
};
