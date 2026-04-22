<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'plan_code',
        'plan_name',
        'billing_status',
        'amount',
        'currency',
        'started_at',
        'mentor_phase_started_at',
        'mentor_phase_ends_at',
        'transitions_to_plan_code',
        'transitions_on',
        'next_billing_at',
        'cancelled_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'mentor_phase_started_at' => 'datetime',
        'mentor_phase_ends_at' => 'datetime',
        'transitions_on' => 'datetime',
        'next_billing_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }
}
