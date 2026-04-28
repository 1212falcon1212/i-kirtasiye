<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Markalar tablosunu oluşturur
     */
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Marka adı');
            $table->string('slug')->unique()->comment('URL için benzersiz slug');
            $table->string('logo_url')->nullable()->comment('Logo görseli URL');
            $table->text('description')->nullable()->comment('Marka açıklaması');
            $table->string('website_url')->nullable()->comment('Marka web sitesi');
            $table->boolean('is_active')->default(true)->index()->comment('Aktif/Pasif durumu');
            $table->boolean('is_featured')->default(false)->index()->comment('Öne çıkan marka');
            $table->unsignedInteger('sort_order')->default(0)->comment('Sıralama');
            $table->timestamps();
        });
    }

    /**
     * Markalar tablosunu kaldırır
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
