<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderPayoutRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'amount',
        'currency',
        'status',
        'destination_summary',
        'notes',
        'requested_at',
        'processed_at',
        'meta_json',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'meta_json' => 'array',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }
}
