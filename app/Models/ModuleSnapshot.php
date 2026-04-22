<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'module',
        'snapshot_version',
        'readiness_score',
        'payload_json',
        'snapshot_updated_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'snapshot_updated_at' => 'datetime',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }
}
