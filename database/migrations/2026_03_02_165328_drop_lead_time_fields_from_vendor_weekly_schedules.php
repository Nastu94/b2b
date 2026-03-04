<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_weekly_schedules', function (Blueprint $table) {
            // Se esistono già, li rimuoviamo (safe)
            if (Schema::hasColumn('vendor_weekly_schedules', 'min_notice_hours')) {
                $table->dropColumn('min_notice_hours');
            }
            if (Schema::hasColumn('vendor_weekly_schedules', 'cutoff_time')) {
                $table->dropColumn('cutoff_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_weekly_schedules', function (Blueprint $table) {
            // Ripristino best-effort
            if (!Schema::hasColumn('vendor_weekly_schedules', 'min_notice_hours')) {
                $table->unsignedSmallInteger('min_notice_hours')->default(48)->after('is_open');
            }
            if (!Schema::hasColumn('vendor_weekly_schedules', 'cutoff_time')) {
                $table->time('cutoff_time')->nullable()->after('min_notice_hours');
            }
        });
    }
};