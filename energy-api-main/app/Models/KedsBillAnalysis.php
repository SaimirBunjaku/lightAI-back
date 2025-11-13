<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KedsBillAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bill_image_path',
        'bill_month',
        'total_kwh',
        'a1_b1_kwh',
        'a2_b1_kwh',
        'a1_b2_kwh',
        'a2_b2_kwh',
        'price_a1_b1',
        'price_a2_b1',
        'price_a1_b2',
        'price_a2_b2',
        'amount_a1_b1',
        'amount_a2_b1',
        'amount_a1_b2',
        'amount_a2_b2',
        'standing_charge',
        'net_total',
        'vat',
        'bill_total',
        'kesco_debt',
        'human_readable_breakdown',
        'device_cost_estimates',
        'insights',
        'raw_ai_response',
    ];

    protected $casts = [
        'human_readable_breakdown' => 'array',
        'device_cost_estimates' => 'array',
        'insights' => 'array',
        'raw_ai_response' => 'array',
    ];

    /**
     * Get the user that owns the bill analysis
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
