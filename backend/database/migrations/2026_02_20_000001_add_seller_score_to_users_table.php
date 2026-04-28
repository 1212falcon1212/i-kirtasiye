<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('seller_score', 3, 1)->nullable()->after('deactivation_reason');
            $table->unsignedInteger('seller_total_orders')->default(0)->after('seller_score');
            $table->unsignedInteger('seller_review_count')->default(0)->after('seller_total_orders');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['seller_score', 'seller_total_orders', 'seller_review_count']);
        });
    }
};
