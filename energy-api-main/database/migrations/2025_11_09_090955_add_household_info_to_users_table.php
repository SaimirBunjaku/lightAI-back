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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('property_ownership', ['own', 'rent'])->nullable()->after('email');
            $table->enum('house_type', [
                'detached',
                'semi_detached',
                'terraced',
                'apartment',
                'flat',
                'bungalow',
                'other'
            ])->nullable()->after('property_ownership');
            $table->unsignedTinyInteger('number_of_occupants')->nullable()->after('house_type');
            $table->unsignedTinyInteger('number_of_bedrooms')->nullable()->after('number_of_occupants');
            $table->enum('heating_type', [
                'gas',
                'electric',
                'oil',
                'solar',
                'heat_pump',
                'biomass',
                'district_heating',
                'other'
            ])->nullable()->after('number_of_bedrooms');
            $table->enum('property_age', [
                'new_build',      // 0-5 years
                'modern',         // 6-20 years
                'established',    // 21-50 years
                'older',          // 51-100 years
                'historic'        // 100+ years
            ])->nullable()->after('heating_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'property_ownership',
                'house_type',
                'number_of_occupants',
                'number_of_bedrooms',
                'heating_type',
                'property_age',
            ]);
        });
    }
};
