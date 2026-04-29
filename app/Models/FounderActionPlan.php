<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderActionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'title',
        'description',
        'platform',
        'context',
        'priority',
        'status',
        'cta_label',
        'cta_url',
        'completed_at',
        'available_on',
        'metadata_json',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'available_on' => 'date',
        'metadata_json' => 'array',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }
}
