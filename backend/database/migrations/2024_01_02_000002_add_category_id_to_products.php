<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Önce mevcut kategorileri categories tablosuna aktar
        $existingCategories = DB::table('products')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        foreach ($existingCategories as $categoryName) {
            if ($categoryName) {
                DB::table('categories')->insertOrIgnore([
                    'name' => $categoryName,
                    'slug' => Str::slug($categoryName),
                    'commission_rate' => 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // category_id kolonunu ekle
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')->constrained()->nullOnDelete();
        });

        // Mevcut category string değerlerini category_id'ye çevir
        $categories = DB::table('categories')->get();
        foreach ($categories as $category) {
            DB::table('products')
                ->where('category', $category->name)
                ->update(['category_id' => $category->id]);
        }

        // Eski category kolonunu kaldır
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category')->nullable()->after('image');
        });

        // category_id'leri category string'e geri çevir
        $categories = DB::table('categories')->get();
        foreach ($categories as $category) {
            DB::table('products')
                ->where('category_id', $category->id)
                ->update(['category' => $category->name]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            $table->index('category');
        });
    }
};
