<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderWalletLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'company_id',
        'source_platform',
        'source_category',
        'source_reference',
        'entry_type',
        'amount',
        'currency',
        'status',
        'available_at',
        'meta_json',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'available_at' => 'datetime',
        'meta_json' => 'array',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
