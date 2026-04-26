<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderPricingRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'company_id',
        'vertical_blueprint_id',
        'founder_action_plan_id',
        'recommendation_key',
        'positioning',
        'title',
        'description',
        'currency',
        'price',
        'status',
        'apply_target',
        'applied_payload_json',
        'meta_json',
        'generated_at',
        'applied_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'applied_payload_json' => 'array',
        'meta_json' => 'array',
        'generated_at' => 'datetime',
        'applied_at' => 'datetime',
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

    public function actionPlan(): BelongsTo
    {
        return $this->belongsTo(FounderActionPlan::class, 'founder_action_plan_id');
    }
}
