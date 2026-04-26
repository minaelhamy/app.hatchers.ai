<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicCheckoutSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'company_id',
        'website_path',
        'platform',
        'category',
        'offer_title',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'amount',
        'currency',
        'payment_method_choice',
        'checkout_status',
        'payload_json',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payload_json' => 'array',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
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
