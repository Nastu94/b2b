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
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE conversation_threads DROP INDEX conv_threads_unique_open');
        } catch (\Exception $e) {
            //
        }

        if (Schema::hasColumn('conversation_threads', 'unique_open_key')) {
            Schema::table('conversation_threads', function (Blueprint $table) {
                $table->dropColumn('unique_open_key');
            });
        }

        Schema::table('conversation_threads', function (Blueprint $table) {
            $expression = "if(`status` = 'open' and `customer_deleted_at` is null, concat_ws('::', ifnull(`prestashop_customer_id`,'null'), `vendor_account_id`, ifnull(`offering_id`,'null')), NULL)";
            $table->string('unique_open_key')->virtualAs($expression)->nullable()->unique('conv_threads_unique_open');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE conversation_threads DROP INDEX conv_threads_unique_open');
        } catch (\Exception $e) {
            //
        }

        if (Schema::hasColumn('conversation_threads', 'unique_open_key')) {
            Schema::table('conversation_threads', function (Blueprint $table) {
                $table->dropColumn('unique_open_key');
            });
        }
    }
};
