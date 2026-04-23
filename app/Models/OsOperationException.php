<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsOperationException extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'operation',
        'founder_id',
        'message',
        'status',
        'payload_json',
        'resolved_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }
}
