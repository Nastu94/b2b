<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_offering_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offering_id')->constrained()->cascadeOnDelete();

            $table->string('title')->nullable();
            $table->string('short_description', 255)->nullable();
            $table->text('description')->nullable();

            // cover image ( path su storage/public)
            $table->string('cover_image_path')->nullable();

            // pubblicazione (visibile/usable per sync o per front)
            $table->boolean('is_published')->default(false);

            $table->timestamps();

            $table->unique(['vendor_account_id', 'offering_id'], 'vendor_offering_profile_unique');
            $table->index(['vendor_account_id']);
            $table->index(['offering_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_offering_profiles');
    }
};