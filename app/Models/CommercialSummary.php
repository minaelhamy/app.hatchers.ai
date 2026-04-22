<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'business_model',
        'product_count',
        'service_count',
        'order_count',
        'booking_count',
        'customer_count',
        'gross_revenue',
        'currency',
        'summary_updated_at',
    ];

    protected $casts = [
        'summary_updated_at' => 'datetime',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }
}
