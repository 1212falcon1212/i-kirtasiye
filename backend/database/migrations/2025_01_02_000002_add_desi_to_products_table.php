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
            // Desi (hacimsel ağırlık) - genişlik x yükseklik x derinlik / 3000
            $table->decimal('desi', 8, 2)->nullable()->after('description');
            // Gerçek ağırlık (kg)
            $table->decimal('weight', 8, 2)->nullable()->after('desi');
            // Boyutlar (cm)
            $table->decimal('width', 8, 2)->nullable()->after('weight');
            $table->decimal('height', 8, 2)->nullable()->after('width');
            $table->decimal('depth', 8, 2)->nullable()->after('height');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['desi', 'weight', 'width', 'height', 'depth']);
        });
    }
};
