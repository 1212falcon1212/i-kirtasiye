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
        // products tablosuna deleted_at ekle
        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
        });

        // offers tablosuna deleted_at ekle
        Schema::table('offers', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
