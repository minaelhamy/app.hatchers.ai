<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderPodMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'founder_pod_id',
        'role',
        'status',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }

    public function pod(): BelongsTo
    {
        return $this->belongsTo(FounderPod::class, 'founder_pod_id');
    }
}
