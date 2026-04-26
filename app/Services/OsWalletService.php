<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Founder;
use App\Models\FounderPayoutRequest;
use App\Models\FounderWalletLedger;

class OsWalletService
{
    public const MINIMUM_PAYOUT_AMOUNT = 50.00;

    public function summary(Founder $founder): array
    {
        $entries = $founder->walletLedgerEntries()->latest()->get();
        $available = (float) $entries->where('status', 'available')->sum('amount');
        $pending = (float) $entries->where('status', 'pending')->sum('amount');
        $reserved = abs((float) $entries->where('status', 'reserved')->sum('amount'));
        $grossSales = (float) $entries
            ->where('entry_type', 'credit')
            ->filter(fn (FounderWalletLedger $entry): bool => in_array((string) $entry->source_category, ['order', 'booking'], true))
            ->sum('amount');
        $platformFees = abs((float) $entries
            ->filter(fn (FounderWalletLedger $entry): bool => (string) $entry->source_category === 'platform_fee')
            ->sum('amount'));
        $refundedGross = (float) $entries
            ->filter(fn (FounderWalletLedger $entry): bool => in_array((string) $entry->source_category, ['order_refund', 'booking_refund'], true))
            ->sum(fn (FounderWalletLedger $entry): float => (float) (($entry->meta_json['gross_amount'] ?? 0)));
        $platformFeeReversals = (float) $entries
            ->filter(fn (FounderWalletLedger $entry): bool => (string) $entry->source_category === 'platform_fee_reversal')
            ->sum('amount');
        $netFees = max(0, $platformFees - $platformFeeReversals);

        return [
            'available_balance' => round($available, 2),
            'pending_balance' => round($pending, 2),
            'reserved_balance' => round($reserved, 2),
            'gross_sales_total' => round($grossSales, 2),
            'refunded_sales_total' => round($refundedGross, 2),
            'platform_fees_total' => round($netFees, 2),
            'net_earnings_total' => round(($grossSales - $refundedGross) - $netFees, 2),
            'minimum_payout_amount' => self::MINIMUM_PAYOUT_AMOUNT,
            'currency' => $this->currencyForFounder($founder),
            'recent_entries' => $entries->take(8)->map(function (FounderWalletLedger $entry): array {
                return [
                    'entry_type' => (string) $entry->entry_type,
                    'amount' => (float) $entry->amount,
                    'currency' => (string) $entry->currency,
                    'status' => (string) $entry->status,
                    'source_platform' => (string) ($entry->source_platform ?? ''),
                    'source_category' => (string) ($entry->source_category ?? ''),
                    'source_reference' => (string) ($entry->source_reference ?? ''),
                    'created_at' => optional($entry->created_at)?->toDateTimeString(),
                ];
            })->values()->all(),
        ];
    }

    public function creditCommerceSale(
        Founder $founder,
        ?Company $company,
        string $platform,
        string $category,
        string $reference,
        float $amount,
        string $currency,
        array $meta = []
    ): FounderWalletLedger {
        $currencyCode = strtoupper(trim($currency)) !== '' ? strtoupper(trim($currency)) : 'USD';
        $settlement = $this->saleSettlement($amount);

        $grossEntry = FounderWalletLedger::query()->firstOrCreate([
            'founder_id' => $founder->id,
            'source_platform' => $platform,
            'source_category' => $category,
            'source_reference' => $reference,
            'entry_type' => 'credit',
        ], [
            'company_id' => $company?->id,
            'amount' => round($settlement['gross_amount'], 2),
            'currency' => $currencyCode,
            'status' => 'available',
            'available_at' => now(),
            'meta_json' => array_merge($meta, [
                'ledger_role' => 'gross_sale',
                'platform_fee_amount' => $settlement['platform_fee_amount'],
                'net_amount' => $settlement['net_amount'],
            ]),
        ]);

        if ($settlement['platform_fee_amount'] > 0) {
            FounderWalletLedger::query()->firstOrCreate([
                'founder_id' => $founder->id,
                'source_platform' => 'os',
                'source_category' => 'platform_fee',
                'source_reference' => $platform . ':' . $category . ':' . $reference,
                'entry_type' => 'debit',
            ], [
                'company_id' => $company?->id,
                'amount' => round(-1 * $settlement['platform_fee_amount'], 2),
                'currency' => $currencyCode,
                'status' => 'available',
                'available_at' => now(),
                'meta_json' => array_merge($meta, [
                    'ledger_role' => 'platform_fee',
                    'sale_platform' => $platform,
                    'sale_category' => $category,
                    'sale_reference' => $reference,
                    'gross_amount' => $settlement['gross_amount'],
                    'fee_percent' => $settlement['fee_percent'],
                    'fee_fixed' => $settlement['fee_fixed'],
                ]),
            ]);
        }

        return $grossEntry;
    }

    public function refundCommerceSale(
        Founder $founder,
        ?Company $company,
        string $platform,
        string $category,
        string $reference,
        float $grossAmount,
        string $currency,
        array $meta = []
    ): FounderWalletLedger {
        $refundCategory = $category . '_refund';
        $existing = FounderWalletLedger::query()
            ->where('founder_id', $founder->id)
            ->where('source_platform', $platform)
            ->where('source_category', $refundCategory)
            ->where('source_reference', $reference)
            ->where('entry_type', 'debit')
            ->first();

        if ($existing) {
            return $existing;
        }

        $currencyCode = strtoupper(trim($currency)) !== '' ? strtoupper(trim($currency)) : 'USD';
        $settlement = $this->saleSettlement($grossAmount);

        $refundEntry = FounderWalletLedger::create([
            'founder_id' => $founder->id,
            'company_id' => $company?->id,
            'source_platform' => $platform,
            'source_category' => $refundCategory,
            'source_reference' => $reference,
            'entry_type' => 'debit',
            'amount' => round(-1 * $settlement['net_amount'], 2),
            'currency' => $currencyCode,
            'status' => 'available',
            'available_at' => now(),
            'meta_json' => array_merge($meta, [
                'ledger_role' => 'sale_refund',
                'gross_amount' => $settlement['gross_amount'],
                'platform_fee_amount' => $settlement['platform_fee_amount'],
                'net_amount' => $settlement['net_amount'],
            ]),
        ]);

        if ($settlement['platform_fee_amount'] > 0) {
            FounderWalletLedger::query()->firstOrCreate([
                'founder_id' => $founder->id,
                'source_platform' => 'os',
                'source_category' => 'platform_fee_reversal',
                'source_reference' => $platform . ':' . $category . ':' . $reference,
                'entry_type' => 'credit',
            ], [
                'company_id' => $company?->id,
                'amount' => round($settlement['platform_fee_amount'], 2),
                'currency' => $currencyCode,
                'status' => 'available',
                'available_at' => now(),
                'meta_json' => array_merge($meta, [
                    'ledger_role' => 'platform_fee_reversal',
                    'sale_platform' => $platform,
                    'sale_category' => $category,
                    'sale_reference' => $reference,
                    'gross_amount' => $settlement['gross_amount'],
                    'fee_percent' => $settlement['fee_percent'],
                    'fee_fixed' => $settlement['fee_fixed'],
                ]),
            ]);
        }

        return $refundEntry;
    }

    public function requestPayout(Founder $founder, float $amount, string $currency, string $destinationSummary, string $notes = ''): FounderPayoutRequest
    {
        return FounderPayoutRequest::create([
            'founder_id' => $founder->id,
            'amount' => round($amount, 2),
            'currency' => strtoupper(trim($currency)) !== '' ? strtoupper(trim($currency)) : 'USD',
            'status' => 'pending',
            'destination_summary' => $destinationSummary,
            'notes' => trim($notes),
            'requested_at' => now(),
            'meta_json' => [],
        ]);
    }

    public function reserveForPayout(Founder $founder, ?Company $company, FounderPayoutRequest $payoutRequest): FounderWalletLedger
    {
        return FounderWalletLedger::create([
            'founder_id' => $founder->id,
            'company_id' => $company?->id,
            'source_platform' => 'os',
            'source_category' => 'payout',
            'source_reference' => (string) $payoutRequest->id,
            'entry_type' => 'debit',
            'amount' => round(-1 * (float) $payoutRequest->amount, 2),
            'currency' => (string) $payoutRequest->currency,
            'status' => 'reserved',
            'available_at' => now(),
            'meta_json' => [
                'payout_request_id' => $payoutRequest->id,
            ],
        ]);
    }

    public function markPayoutPaid(FounderPayoutRequest $payoutRequest, string $reference = ''): FounderPayoutRequest
    {
        if (in_array((string) $payoutRequest->status, ['paid', 'completed'], true)) {
            return $payoutRequest;
        }

        $reservedEntry = $this->payoutReserveEntry($payoutRequest);
        if ($reservedEntry) {
            $reservedEntry->forceFill([
                'status' => 'settled',
                'meta_json' => array_merge((array) ($reservedEntry->meta_json ?? []), [
                    'payout_status' => 'paid',
                    'payout_reference' => trim($reference),
                ]),
            ])->save();
        }

        $payoutRequest->forceFill([
            'status' => 'paid',
            'processed_at' => now(),
            'meta_json' => array_merge((array) ($payoutRequest->meta_json ?? []), [
                'paid_reference' => trim($reference),
                'paid_at' => now()->toDateTimeString(),
            ]),
        ])->save();

        return $payoutRequest->fresh();
    }

    public function rejectPayout(FounderPayoutRequest $payoutRequest, string $reason = ''): FounderPayoutRequest
    {
        if ((string) $payoutRequest->status === 'rejected') {
            return $payoutRequest;
        }

        $reservedEntry = $this->payoutReserveEntry($payoutRequest);
        if ($reservedEntry) {
            $reservedEntry->forceFill([
                'status' => 'released',
                'meta_json' => array_merge((array) ($reservedEntry->meta_json ?? []), [
                    'payout_status' => 'rejected',
                    'rejection_reason' => trim($reason),
                ]),
            ])->save();
        }

        FounderWalletLedger::query()->firstOrCreate([
            'founder_id' => $payoutRequest->founder_id,
            'source_platform' => 'os',
            'source_category' => 'payout_reversal',
            'source_reference' => (string) $payoutRequest->id,
            'entry_type' => 'credit',
        ], [
            'company_id' => $payoutRequest->founder?->company?->id,
            'amount' => round((float) $payoutRequest->amount, 2),
            'currency' => (string) $payoutRequest->currency,
            'status' => 'available',
            'available_at' => now(),
            'meta_json' => [
                'payout_request_id' => $payoutRequest->id,
                'rejection_reason' => trim($reason),
            ],
        ]);

        $payoutRequest->forceFill([
            'status' => 'rejected',
            'processed_at' => now(),
            'meta_json' => array_merge((array) ($payoutRequest->meta_json ?? []), [
                'rejection_reason' => trim($reason),
                'rejected_at' => now()->toDateTimeString(),
            ]),
        ])->save();

        return $payoutRequest->fresh();
    }

    public function canRequestPayout(Founder $founder, float $amount): bool
    {
        $summary = $this->summary($founder);

        return $amount >= self::MINIMUM_PAYOUT_AMOUNT && $amount <= (float) $summary['available_balance'];
    }

    public function workspace(Founder $founder, array $filters = []): array
    {
        $summary = $this->summary($founder);
        $entryType = (string) ($filters['entry_type'] ?? 'all');
        $entryStatus = (string) ($filters['entry_status'] ?? 'all');
        $payoutStatus = (string) ($filters['payout_status'] ?? 'all');
        $search = trim((string) ($filters['q'] ?? ''));

        $ledgerQuery = $founder->walletLedgerEntries()->latest();
        if ($entryType !== '' && $entryType !== 'all') {
            $ledgerQuery->where('entry_type', $entryType);
        }
        if ($entryStatus !== '' && $entryStatus !== 'all') {
            $ledgerQuery->where('status', $entryStatus);
        }
        if ($search !== '') {
            $ledgerQuery->where(function ($query) use ($search): void {
                $query->where('source_reference', 'like', '%' . $search . '%')
                    ->orWhere('source_platform', 'like', '%' . $search . '%')
                    ->orWhere('source_category', 'like', '%' . $search . '%');
            });
        }

        $payoutQuery = $founder->payoutRequests()->latest();
        if ($payoutStatus !== '' && $payoutStatus !== 'all') {
            $payoutQuery->where('status', $payoutStatus);
        }
        if ($search !== '') {
            $payoutQuery->where(function ($query) use ($search): void {
                $query->where('destination_summary', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhere('currency', 'like', '%' . $search . '%');
            });
        }

        return [
            'summary' => $summary,
            'ledger_entries' => $ledgerQuery->limit(80)->get()->map(function (FounderWalletLedger $entry): array {
                $meta = is_array($entry->meta_json) ? $entry->meta_json : [];

                return [
                    'id' => (int) $entry->id,
                    'entry_type' => (string) $entry->entry_type,
                    'amount' => (float) $entry->amount,
                    'amount_display' => number_format(abs((float) $entry->amount), 2),
                    'currency' => (string) $entry->currency,
                    'status' => (string) $entry->status,
                    'status_label' => ucfirst((string) $entry->status),
                    'source_platform' => (string) $entry->source_platform,
                    'source_category' => (string) $entry->source_category,
                    'source_category_label' => $this->sourceCategoryLabel((string) $entry->source_category),
                    'source_reference' => (string) ($entry->source_reference ?? ''),
                    'created_at' => optional($entry->created_at)?->toDateTimeString(),
                    'available_at' => optional($entry->available_at)?->toDateTimeString(),
                    'headline' => $this->ledgerHeadline($entry),
                    'note' => $this->ledgerNote($entry),
                    'related_url' => $this->ledgerRelatedUrl($entry),
                    'related_label' => $this->ledgerRelatedLabel($entry),
                    'meta' => $meta,
                ];
            })->values()->all(),
            'payout_requests' => $payoutQuery->limit(40)->get()->map(function (FounderPayoutRequest $request): array {
                $meta = is_array($request->meta_json) ? $request->meta_json : [];

                return [
                    'id' => (int) $request->id,
                    'amount' => (float) $request->amount,
                    'amount_display' => number_format((float) $request->amount, 2),
                    'currency' => (string) $request->currency,
                    'status' => (string) $request->status,
                    'status_label' => ucfirst((string) $request->status),
                    'destination_summary' => (string) ($request->destination_summary ?? ''),
                    'notes' => (string) ($request->notes ?? ''),
                    'requested_at' => optional($request->requested_at)?->toDateTimeString(),
                    'processed_at' => optional($request->processed_at)?->toDateTimeString(),
                    'reference' => (string) ($meta['paid_reference'] ?? ''),
                    'rejection_reason' => (string) ($meta['rejection_reason'] ?? ''),
                    'related_url' => route('founder.commerce'),
                    'related_label' => 'Open payout controls',
                    'meta' => $meta,
                ];
            })->values()->all(),
            'filters' => [
                'entry_type' => $entryType !== '' ? $entryType : 'all',
                'entry_status' => $entryStatus !== '' ? $entryStatus : 'all',
                'payout_status' => $payoutStatus !== '' ? $payoutStatus : 'all',
                'q' => $search,
            ],
            'entry_type_options' => ['all', 'credit', 'debit'],
            'entry_status_options' => ['all', 'available', 'pending', 'reserved', 'settled', 'released'],
            'payout_status_options' => ['all', 'pending', 'processing', 'paid', 'rejected'],
        ];
    }

    private function currencyForFounder(Founder $founder): string
    {
        $snapshot = $founder->moduleSnapshots()->whereIn('module', ['bazaar', 'servio'])->latest('snapshot_updated_at')->first();
        $payload = is_array($snapshot?->payload_json) ? $snapshot->payload_json : [];
        $summary = is_array($payload['summary'] ?? null) ? $payload['summary'] : [];
        $currency = strtoupper(trim((string) ($summary['currency'] ?? 'USD')));

        return $currency !== '' ? $currency : 'USD';
    }

    private function sourceCategoryLabel(string $category): string
    {
        return match ($category) {
            'order' => 'Product sale',
            'booking' => 'Service sale',
            'platform_fee' => 'Platform fee',
            'platform_fee_reversal' => 'Platform fee reversal',
            'order_refund' => 'Order refund',
            'booking_refund' => 'Booking refund',
            'payout' => 'Withdrawal reserve',
            'payout_reversal' => 'Withdrawal reversal',
            'manual_adjustment' => 'Manual adjustment',
            default => ucwords(str_replace('_', ' ', $category)),
        };
    }

    private function ledgerHeadline(FounderWalletLedger $entry): string
    {
        $reference = trim((string) ($entry->source_reference ?? ''));
        $label = $this->sourceCategoryLabel((string) $entry->source_category);

        return $reference !== '' ? $label . ' · ' . $reference : $label;
    }

    private function ledgerNote(FounderWalletLedger $entry): string
    {
        $meta = is_array($entry->meta_json) ? $entry->meta_json : [];

        if (!empty($meta['reason'])) {
            return (string) $meta['reason'];
        }
        if (!empty($meta['rejection_reason'])) {
            return 'Reason: ' . (string) $meta['rejection_reason'];
        }
        if (!empty($meta['sale_platform']) && !empty($meta['sale_reference'])) {
            return 'Linked to ' . ucfirst((string) $meta['sale_platform']) . ' sale ' . (string) $meta['sale_reference'];
        }
        if (!empty($meta['ledger_role'])) {
            return ucwords(str_replace('_', ' ', (string) $meta['ledger_role']));
        }

        return '';
    }

    private function ledgerRelatedUrl(FounderWalletLedger $entry): string
    {
        return match ((string) $entry->source_category) {
            'order', 'order_refund' => route('founder.commerce.orders', ['q' => (string) ($entry->source_reference ?? '')]),
            'booking', 'booking_refund' => route('founder.commerce.bookings', ['q' => (string) ($entry->source_reference ?? '')]),
            'payout', 'payout_reversal' => route('founder.commerce'),
            'platform_fee', 'platform_fee_reversal' => route('founder.commerce.wallet', ['q' => (string) ($entry->source_reference ?? '')]),
            default => route('founder.commerce.wallet', ['q' => (string) ($entry->source_reference ?? '')]),
        };
    }

    private function ledgerRelatedLabel(FounderWalletLedger $entry): string
    {
        return match ((string) $entry->source_category) {
            'order', 'order_refund' => 'Open related order',
            'booking', 'booking_refund' => 'Open related booking',
            'payout', 'payout_reversal' => 'Open payout controls',
            'platform_fee', 'platform_fee_reversal' => 'View fee trail',
            default => 'Open related activity',
        };
    }

    private function saleSettlement(float $grossAmount): array
    {
        $grossAmount = round(max(0, $grossAmount), 2);
        $feePercent = max(0, (float) config('services.stripe.platform_fee_percent', 0));
        $feeFixed = max(0, (float) config('services.stripe.platform_fee_fixed', 0));
        $percentageFee = $grossAmount * ($feePercent / 100);
        $platformFee = min($grossAmount, round($percentageFee + $feeFixed, 2));
        $netAmount = round(max(0, $grossAmount - $platformFee), 2);

        return [
            'gross_amount' => $grossAmount,
            'platform_fee_amount' => $platformFee,
            'net_amount' => $netAmount,
            'fee_percent' => $feePercent,
            'fee_fixed' => $feeFixed,
        ];
    }

    private function payoutReserveEntry(FounderPayoutRequest $payoutRequest): ?FounderWalletLedger
    {
        return FounderWalletLedger::query()
            ->where('founder_id', $payoutRequest->founder_id)
            ->where('source_platform', 'os')
            ->where('source_category', 'payout')
            ->where('source_reference', (string) $payoutRequest->id)
            ->where('entry_type', 'debit')
            ->first();
    }
}
