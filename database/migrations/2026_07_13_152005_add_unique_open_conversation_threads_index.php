<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rileva gruppi duplicati
        $duplicates = DB::select("
            SELECT prestashop_customer_id, vendor_account_id, COALESCE(offering_id, 'null') as offering, COUNT(*) as c
            FROM conversation_threads
            WHERE status = 'open' AND customer_deleted_at IS NULL
            GROUP BY prestashop_customer_id, vendor_account_id, COALESCE(offering_id, 'null')
            HAVING COUNT(*) > 1
        ");

        if (count($duplicates) > 0) {
            $message = "Trovate conversazioni duplicate attive. Bonificare manualmente prima di applicare la migrazione:\n";
            foreach ($duplicates as $dup) {
                $message .= "- Customer: {$dup->prestashop_customer_id}, Vendor: {$dup->vendor_account_id}, Offering: {$dup->offering} (Conteggio: {$dup->c})\n";
            }
            throw new \RuntimeException($message);
        }

        Schema::table('conversation_threads', function (Blueprint $table) {
            $table->string('unique_open_key')->nullable()->unique('conv_threads_unique_open');
        });

        // Valorizza le chiavi esistenti
        $threads = DB::table('conversation_threads')->where('status', 'open')->whereNull('customer_deleted_at')->get();
        foreach ($threads as $thread) {
            $offering = $thread->offering_id ?? 'null';
            $key = "{$thread->prestashop_customer_id}::{$thread->vendor_account_id}::{$offering}";
            DB::table('conversation_threads')->where('id', $thread->id)->update(['unique_open_key' => $key]);
        }
    }

    public function down(): void
    {
        Schema::table('conversation_threads', function (Blueprint $table) {
            $table->dropUnique('conv_threads_unique_open');
            $table->dropColumn('unique_open_key');
        });
    }
};
