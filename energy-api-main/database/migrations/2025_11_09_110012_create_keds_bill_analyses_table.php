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
        Schema::create('keds_bill_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('bill_image_path'); // Path to uploaded bill image
            $table->string('bill_month')->nullable(); // e.g., "November 2025"

            // Consumption data from KEDS bill
            $table->decimal('total_kwh', 10, 2)->nullable(); // Total kilowatt-hours
            $table->decimal('a1_b1_kwh', 10, 2)->nullable(); // Daytime consumption
            $table->decimal('a2_b1_kwh', 10, 2)->nullable(); // Nighttime consumption
            $table->decimal('a1_b2_kwh', 10, 2)->nullable(); // Peak daytime (if applicable)
            $table->decimal('a2_b2_kwh', 10, 2)->nullable(); // Peak nighttime (if applicable)

            // Pricing from KEDS bill
            $table->decimal('price_a1_b1', 10, 4)->nullable(); // Price per kWh for A1/B1
            $table->decimal('price_a2_b1', 10, 4)->nullable(); // Price per kWh for A2/B1
            $table->decimal('price_a1_b2', 10, 4)->nullable(); // Price per kWh for A1/B2
            $table->decimal('price_a2_b2', 10, 4)->nullable(); // Price per kWh for A2/B2

            // Costs from KEDS bill
            $table->decimal('amount_a1_b1', 10, 2)->nullable(); // Cost for A1/B1
            $table->decimal('amount_a2_b1', 10, 2)->nullable(); // Cost for A2/B1
            $table->decimal('amount_a1_b2', 10, 2)->nullable(); // Cost for A1/B2
            $table->decimal('amount_a2_b2', 10, 2)->nullable(); // Cost for A2/B2
            $table->decimal('standing_charge', 10, 2)->nullable(); // Fixed tariff
            $table->decimal('net_total', 10, 2)->nullable(); // Net amount
            $table->decimal('vat', 10, 2)->nullable(); // VAT/TVSH
            $table->decimal('bill_total', 10, 2)->nullable(); // Total bill amount
            $table->decimal('kesco_debt', 10, 2)->default(0); // Any outstanding debt

            // AI-generated insights
            $table->json('human_readable_breakdown')->nullable(); // Translation of tariffs
            $table->json('device_cost_estimates')->nullable(); // Estimated costs per device
            $table->json('insights')->nullable(); // AI-generated insights and tips
            $table->json('raw_ai_response')->nullable(); // Full AI response for debugging

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keds_bill_analyses');
    }
};
