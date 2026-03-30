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
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'commission_rate')) {
                $table->decimal('commission_rate', 5, 2)->default(0.00);
            }
        });

        Schema::table('vendor_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_accounts', 'status')) {
                $table->string('status')->default('PENDING');
            }
            if (!Schema::hasColumn('vendor_accounts', 'payment_model')) {
                $table->enum('payment_model', ['COMMISSION', 'SUBSCRIPTION'])->default('COMMISSION');
            }
        });

        Schema::table('vendor_offering_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_offering_profiles', 'is_approved')) {
                $table->boolean('is_approved')->default(false);
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'is_commission_based')) {
                $table->boolean('is_commission_based')->default(true);
            }
            if (!Schema::hasColumn('bookings', 'commission_rate')) {
                $table->decimal('commission_rate', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('bookings', 'commission_amount')) {
                $table->decimal('commission_amount', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('bookings', 'vendor_payment_status')) {
                $table->enum('vendor_payment_status', ['UNPAID', 'PAID'])->default('UNPAID');
            }
            if (!Schema::hasColumn('bookings', 'vendor_transaction_id')) {
                $table->string('vendor_transaction_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'is_commission_based',
                'commission_rate',
                'commission_amount',
                'vendor_payment_status',
                'vendor_transaction_id'
            ]);
        });

        Schema::table('vendor_offering_profiles', function (Blueprint $table) {
            $table->dropColumn('is_approved');
        });

        Schema::table('vendor_accounts', function (Blueprint $table) {
            $table->dropColumn(['status', 'payment_model']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });
    }
};
