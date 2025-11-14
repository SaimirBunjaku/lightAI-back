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
        Schema::create('device_analyses', function (Blueprint $table) {
            $table->id();
            $table->string('image_path');
            $table->string('device_category')->nullable();
            $table->string('device_brand')->nullable();
            $table->string('device_model')->nullable();
            $table->string('confidence_level')->nullable(); // high, medium, low
            $table->string('fallback_level')->default('specific'); // specific, category, generic
            $table->string('typical_wattage')->nullable();
            $table->string('daily_kwh')->nullable();
            $table->string('annual_kwh')->nullable();
            $table->string('estimated_annual_cost')->nullable();
            $table->json('energy_saving_tips')->nullable();
            $table->json('raw_ai_response')->nullable(); // Store complete AI response
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_analyses');
    }
};
