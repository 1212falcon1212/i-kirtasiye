<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Modify the status enum to include pending and rejected (MySQL only)
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE offers MODIFY COLUMN status ENUM('pending', 'active', 'inactive', 'sold_out', 'rejected') NOT NULL DEFAULT 'pending'");
        }
        // SQLite: status is already a string type, no modification needed

        // Add admin review fields
        Schema::table('offers', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->foreignId('reviewed_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                $table->text('rejection_reason')->nullable()->after('reviewed_at');
            } else {
                // SQLite doesn't support after() or foreign key constraints in the same way
                if (!Schema::hasColumn('offers', 'reviewed_by')) {
                    $table->unsignedBigInteger('reviewed_by')->nullable();
                    $table->timestamp('reviewed_at')->nullable();
                    $table->text('rejection_reason')->nullable();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Remove admin review fields
        Schema::table('offers', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->dropForeign(['reviewed_by']);
            }
            $table->dropColumn(['reviewed_by', 'reviewed_at', 'rejection_reason']);
        });

        // Revert status enum (MySQL only)
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE offers MODIFY COLUMN status ENUM('active', 'inactive', 'sold_out') NOT NULL DEFAULT 'active'");
        }
    }
};
