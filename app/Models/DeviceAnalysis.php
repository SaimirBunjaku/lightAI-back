<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image_path',
        'device_category',
        'device_brand',
        'device_model',
        'confidence_level',
        'fallback_level',
        'typical_wattage',
        'daily_kwh',
        'annual_kwh',
        'estimated_annual_cost',
        'energy_saving_tips',
        'raw_ai_response',
    ];

    protected $casts = [
        'energy_saving_tips' => 'array',
        'raw_ai_response' => 'array',
    ];

    /**
     * Get the user that owns the analysis
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
