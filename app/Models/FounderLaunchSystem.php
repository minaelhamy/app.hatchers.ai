<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderLaunchSystem extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'company_id',
        'vertical_blueprint_id',
        'founder_website_generation_run_id',
        'status',
        'selected_engine',
        'launch_strategy_json',
        'funnel_blocks_json',
        'offer_stack_json',
        'acquisition_system_json',
        'applied_at',
        'last_reviewed_at',
    ];

    protected $casts = [
        'launch_strategy_json' => 'array',
        'funnel_blocks_json' => 'array',
        'offer_stack_json' => 'array',
        'acquisition_system_json' => 'array',
        'applied_at' => 'datetime',
        'last_reviewed_at' => 'datetime',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function verticalBlueprint(): BelongsTo
    {
        return $this->belongsTo(VerticalBlueprint::class);
    }

    public function websiteGenerationRun(): BelongsTo
    {
        return $this->belongsTo(FounderWebsiteGenerationRun::class, 'founder_website_generation_run_id');
    }
}
