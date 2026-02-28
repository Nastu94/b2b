<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_offering_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_offering_profile_id')
                ->constrained('vendor_offering_profiles')
                ->cascadeOnDelete();

            $table->string('path'); // storage path (public)
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['vendor_offering_profile_id', 'sort_order'], 'voi_profile_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_offering_images');
    }
};