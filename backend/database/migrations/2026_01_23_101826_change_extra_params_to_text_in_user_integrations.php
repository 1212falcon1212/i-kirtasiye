<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, get existing data
        $existingData = DB::table('user_integrations')->get();

        // Change column type from JSON to TEXT
        Schema::table('user_integrations', function (Blueprint $table) {
            $table->text('extra_params')->nullable()->change();
        });

        // Encrypt existing data
        foreach ($existingData as $row) {
            if (!empty($row->extra_params)) {
                // Parse the JSON if it's valid JSON (not already encrypted)
                $data = json_decode($row->extra_params, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    // It's valid JSON, encrypt it
                    $encrypted = Crypt::encryptString(json_encode($data));

                    DB::table('user_integrations')
                        ->where('id', $row->id)
                        ->update(['extra_params' => $encrypted]);
                }
                // If it's not valid JSON, it might already be encrypted, skip
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get existing encrypted data and decrypt
        $existingData = DB::table('user_integrations')->get();

        foreach ($existingData as $row) {
            if (!empty($row->extra_params)) {
                try {
                    $decrypted = Crypt::decryptString($row->extra_params);
                    DB::table('user_integrations')
                        ->where('id', $row->id)
                        ->update(['extra_params' => $decrypted]);
                } catch (\Exception $e) {
                    // Already decrypted or invalid, skip
                }
            }
        }

        // Change column type back to JSON
        Schema::table('user_integrations', function (Blueprint $table) {
            $table->json('extra_params')->nullable()->change();
        });
    }
};
