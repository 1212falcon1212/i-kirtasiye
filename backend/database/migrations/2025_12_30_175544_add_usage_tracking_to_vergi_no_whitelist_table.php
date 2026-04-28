<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vergi_no_whitelist', function (Blueprint $table) {
            $table->boolean('is_used')->default(false)->after('is_active');
            $table->foreignId('used_by_user_id')->nullable()->after('is_used')->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable()->after('used_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('vergi_no_whitelist', function (Blueprint $table) {
            $table->dropForeign(['used_by_user_id']);
            $table->dropColumn(['is_used', 'used_by_user_id', 'used_at']);
        });
    }
};
