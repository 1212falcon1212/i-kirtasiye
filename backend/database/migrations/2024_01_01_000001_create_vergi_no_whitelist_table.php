<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vergi_no_whitelist', function (Blueprint $table) {
            $table->id();
            $table->string('vergi_no', 11)->unique();
            $table->string('company_name');
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('vergi_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vergi_no_whitelist');
    }
};
