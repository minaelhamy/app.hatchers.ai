<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'mentor_user_id',
        'status',
        'assigned_at',
        'ended_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class, 'founder_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Founder::class, 'mentor_user_id');
    }
}
