<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderPromoLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'company_id',
        'title',
        'source_channel',
        'promo_code',
        'destination_path',
        'cta_label',
        'offer_title',
        'status',
        'meta_json',
    ];

    protected $casts = [
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
