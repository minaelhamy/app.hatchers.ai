<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderIcpProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'company_id',
        'primary_icp_name',
        'age_range',
        'gender_focus',
        'life_stage',
        'pain_points_json',
        'desired_outcomes_json',
        'buying_triggers_json',
        'objections_json',
        'price_sensitivity',
        'primary_channels_json',
        'local_area_focus_json',
        'language_style',
    ];

    protected $casts = [
        'pain_points_json' => 'array',
        'desired_outcomes_json' => 'array',
        'buying_triggers_json' => 'array',
        'objections_json' => 'array',
        'primary_channels_json' => 'array',
        'local_area_focus_json' => 'array',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
