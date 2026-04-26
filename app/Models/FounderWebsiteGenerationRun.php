<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FounderWebsiteGenerationRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'company_id',
        'vertical_blueprint_id',
        'engine',
        'status',
        'input_json',
        'output_json',
        'generated_at',
    ];

    protected $casts = [
        'input_json' => 'array',
        'output_json' => 'array',
        'generated_at' => 'datetime',
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

    public function launchSystems(): HasMany
    {
        return $this->hasMany(FounderLaunchSystem::class, 'founder_website_generation_run_id');
    }
}
