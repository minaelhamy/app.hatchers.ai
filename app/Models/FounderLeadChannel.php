<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderLeadChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'company_id',
        'vertical_blueprint_id',
        'channel_key',
        'channel_label',
        'status',
        'priority_rank',
        'daily_target',
        'script_title',
        'script_body',
        'offer_angle',
        'meta_json',
        'adopted_at',
        'last_used_at',
    ];

    protected $casts = [
        'meta_json' => 'array',
        'adopted_at' => 'datetime',
        'last_used_at' => 'datetime',
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
