<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['return', 'cancel'])->default('return');
            $table->enum('reason', [
                'wrong_product',
                'damaged',
                'not_as_described',
                'quality_issue',
                'expired',
                'changed_mind',
                'other'
            ]);
            $table->text('reason_detail')->nullable();
            $table->json('images')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'shipped',
                'received',
                'refunded',
                'cancelled'
            ])->default('pending');
            $table->text('seller_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('return_tracking_number')->nullable();
            $table->string('return_shipping_provider')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['buyer_id', 'status']);
            $table->index(['seller_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_requests');
    }
};
