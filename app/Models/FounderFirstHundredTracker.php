<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderFirstHundredTracker extends Model
{
    use HasFactory;

    protected $table = 'founder_first_100_trackers';

    protected $fillable = [
        'founder_id',
        'company_id',
        'vertical_blueprint_id',
        'status',
        'target_customers',
        'customers_won',
        'active_leads',
        'follow_up_due',
        'best_channel',
        'progress_percent',
        'daily_plan_json',
        'acquisition_summary_json',
        'last_synced_at',
    ];

    protected $casts = [
        'daily_plan_json' => 'array',
        'acquisition_summary_json' => 'array',
        'last_synced_at' => 'datetime',
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
}
