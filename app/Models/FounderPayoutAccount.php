<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderPayoutAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'account_holder_name',
        'bank_name',
        'account_number',
        'iban',
        'swift_code',
        'routing_number',
        'bank_country',
        'bank_currency',
        'stripe_account_id',
        'stripe_onboarding_status',
        'stripe_charges_enabled',
        'stripe_payouts_enabled',
        'stripe_details_submitted_at',
        'stripe_payouts_enabled_at',
        'status',
        'notes',
        'meta_json',
    ];

    protected $casts = [
        'stripe_charges_enabled' => 'boolean',
        'stripe_payouts_enabled' => 'boolean',
        'stripe_details_submitted_at' => 'datetime',
        'stripe_payouts_enabled_at' => 'datetime',
        'meta_json' => 'array',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }
}
