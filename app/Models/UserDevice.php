<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_analysis_id',
        'device_name',
        'device_category',
        'device_brand',
        'device_model',
        'location',
        'typical_wattage',
        'daily_kwh',
        'annual_kwh',
        'estimated_annual_cost',
        'energy_saving_tips',
        'is_active',
    ];

    protected $casts = [
        'energy_saving_tips' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the device
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the original device analysis (if saved from analysis)
     */
    public function deviceAnalysis()
    {
        return $this->belongsTo(DeviceAnalysis::class);
    }
}
