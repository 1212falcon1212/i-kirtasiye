<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('ticket_number')->nullable()->unique()->after('id');
        });

        // Mevcut kayıtlara ticket_number ata
        $tickets = DB::table('support_tickets')->orderBy('id')->get();
        foreach ($tickets as $ticket) {
            DB::table('support_tickets')
                ->where('id', $ticket->id)
                ->update(['ticket_number' => 'TK-' . str_pad($ticket->id, 6, '0', STR_PAD_LEFT)]);
        }
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn('ticket_number');
        });
    }
};
