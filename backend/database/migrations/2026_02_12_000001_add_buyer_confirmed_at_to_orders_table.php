<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('buyer_confirmed_at')->nullable()->after('delivered_at');
        });

        // Backfill: existing delivered orders get buyer_confirmed_at = delivered_at
        DB::table('orders')
            ->where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->update(['buyer_confirmed_at' => DB::raw('delivered_at')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('buyer_confirmed_at');
        });
    }
};
