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
        $duplicate = DB::table('conversation_threads')
            ->selectRaw('prestashop_customer_id, vendor_account_id, offering_id, COUNT(*) AS aggregate')
            ->where('status', 'open')
            ->whereNull('customer_deleted_at')
            ->groupBy('prestashop_customer_id', 'vendor_account_id', 'offering_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate) {
            throw new \RuntimeException(
                'Impossibile applicare il vincolo conversazioni: esistono thread aperti duplicati. Bonificare i dati prima del deploy.'
            );
        }

        if ($this->indexExists('conversation_threads', 'conv_threads_unique_open')) {
            Schema::table('conversation_threads', function (Blueprint $table) {
                $table->dropUnique('conv_threads_unique_open');
            });
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
        if ($this->indexExists('conversation_threads', 'conv_threads_unique_open')) {
            Schema::table('conversation_threads', function (Blueprint $table) {
                $table->dropUnique('conv_threads_unique_open');
            });
        }

        if (Schema::hasColumn('conversation_threads', 'unique_open_key')) {
            Schema::table('conversation_threads', function (Blueprint $table) {
                $table->dropColumn('unique_open_key');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => ($index['name'] ?? null) === $indexName);
    }
};
