<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_user_id',
        'actor_role',
        'action',
        'subject_type',
        'subject_id',
        'summary',
        'metadata_json',
    ];

    protected $casts = [
        'metadata_json' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Founder::class, 'actor_user_id');
    }
}
