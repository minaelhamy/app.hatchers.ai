<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyIntelligence extends Model
{
    use HasFactory;

    protected $table = 'company_intelligence';

    protected $fillable = [
        'company_id',
        'target_audience',
        'ideal_customer_profile',
        'brand_voice',
        'differentiators',
        'content_goals',
        'visual_style',
        'core_offer',
        'pricing_notes',
        'primary_growth_goal',
        'known_blockers',
        'last_summary',
        'intelligence_updated_at',
    ];

    protected $casts = [
        'intelligence_updated_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
