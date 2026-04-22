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
        'priority',
        'status',
        'cta_label',
        'cta_url',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }
}
