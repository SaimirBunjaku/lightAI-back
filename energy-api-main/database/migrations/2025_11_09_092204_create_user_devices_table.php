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
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_analysis_id')->nullable()->constrained('device_analyses')->onDelete('set null');
            $table->string('device_name'); // User's custom name for the device
            $table->string('device_category');
            $table->string('device_brand')->nullable();
            $table->string('device_model')->nullable();
            $table->string('location')->nullable(); // e.g., "Kitchen", "Living Room", "Bedroom"
            $table->string('typical_wattage')->nullable();
            $table->string('daily_kwh')->nullable();
            $table->string('annual_kwh')->nullable();
            $table->string('estimated_annual_cost')->nullable();
            $table->json('energy_saving_tips')->nullable();
            $table->boolean('is_active')->default(true); // Whether device is still in use
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
