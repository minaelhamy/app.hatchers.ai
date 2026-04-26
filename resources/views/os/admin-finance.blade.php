@extends('os.layout')

@section('content')
    @php
        $admin = $workspace['admin'];
        $metrics = $workspace['metrics'];
        $walletRows = $workspace['wallet_rows'];
        $payoutRequests = $workspace['payout_requests'];
        $recentLedgerEntries = $workspace['recent_ledger_entries'];
        $checkoutRows = $workspace['checkout_rows'];
        $filters = $workspace['filters'];
        $payoutStatusOptions = $workspace['payout_status_options'];
        $checkoutStatusOptions = $workspace['checkout_status_options'];
    @endphp

    <div class="sidebar-layout">
        <aside class="sidebar-card">
            <div class="pill">Finance Control</div>
            <div class="nav-group" style="margin-top: 18px;">
                <div class="nav-group-title">Control Center</div>
                <a class="nav-item" href="/dashboard/admin">Overview</a>
                <a class="nav-item" href="{{ route('admin.subscribers') }}">Subscribers</a>
                <a class="nav-item" href="/admin/control">Founder Operations</a>
                <a class="nav-item" href="{{ route('admin.system-access') }}">System Access</a>
                <a class="nav-item" href="{{ route('admin.identity') }}">Identity</a>
                <a class="nav-item" href="{{ route('admin.commerce') }}">Commerce Control</a>
                <a class="nav-item active" href="{{ route('admin.finance') }}">Finance Control</a>
                <a class="nav-item" href="{{ route('admin.modules') }}">Module Monitoring</a>
                <a class="nav-item" href="{{ route('admin.support') }}">Support Center</a>
                <a class="nav-item" href="/dashboard">OS Home</a>
            </div>
        </aside>

        <div>
            <section class="hero">
                <div class="eyebrow">Finance Control</div>
                <h1>Track founder balances, payouts, fees, refunds, and disputes from the OS.</h1>
                <p class="muted">Welcome back, {{ $admin->full_name }}. This workspace gives finance and ops one place to understand founder wallet state and payment reversals across Bazaar and Servio.</p>
            </section>

            @if (session('success'))
                <section class="card" style="border-color: rgba(44, 122, 87, 0.25); background: rgba(44, 122, 87, 0.06); margin-bottom: 18px;">
                    <h3 style="color: var(--success);">Action completed</h3>
                    <p class="muted" style="margin-top: 8px;">{{ session('success') }}</p>
                </section>
            @endif

            @if (session('error'))
                <section class="card" style="border-color: rgba(179, 34, 83, 0.25); background: rgba(179, 34, 83, 0.06); margin-bottom: 18px;">
                    <h3 style="color: var(--rose);">Something needs attention</h3>
                    <p class="muted" style="margin-top: 8px;">{{ session('error') }}</p>
                </section>
            @endif

            <section class="metrics">
                <div class="card metric"><div class="muted">Founders with wallets</div><strong>{{ $metrics['founders_with_wallets'] }}</strong></div>
                <div class="card metric"><div class="muted">Available to founders</div><strong>USD {{ number_format($metrics['wallet_available_total'], 2) }}</strong></div>
                <div class="card metric"><div class="muted">Reserved payouts</div><strong>USD {{ number_format($metrics['wallet_reserved_total'], 2) }}</strong></div>
                <div class="card metric"><div class="muted">Gross sales</div><strong>USD {{ number_format($metrics['gross_sales_total'], 2) }}</strong></div>
                <div class="card metric"><div class="muted">Refunded sales</div><strong>USD {{ number_format($metrics['refunded_sales_total'], 2) }}</strong></div>
                <div class="card metric"><div class="muted">Platform fees</div><strong>USD {{ number_format($metrics['platform_fees_total'], 2) }}</strong></div>
                <div class="card metric"><div class="muted">Open payouts</div><strong>{{ $metrics['open_payout_requests'] }}</strong></div>
                <div class="card metric"><div class="muted">Disputed checkouts</div><strong>{{ $metrics['disputed_checkouts'] }}</strong></div>
                <div class="card metric"><div class="muted">Refunded checkouts</div><strong>{{ $metrics['refunded_checkouts'] }}</strong></div>
                <div class="card metric"><div class="muted">Need finance review</div><strong>{{ $metrics['checkouts_needing_review'] }}</strong></div>
            </section>

            <section class="card" style="margin-top:22px;">
                <h2>Finance Filters</h2>
                <form method="GET" action="{{ route('admin.finance') }}" class="grid-2" style="margin-top: 14px;">
                    <label>
                        <span class="muted">Search founder or company</span>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Founder, email, company" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                    </label>
                    <label>
                        <span class="muted">Payout status</span>
                        <select name="payout_status" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                            <option value="">All payout statuses</option>
                            @foreach ($payoutStatusOptions as $option)
                                <option value="{{ $option }}" @selected(($filters['payout_status'] ?? '') === $option)>{{ ucfirst($option) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="muted">Checkout status</span>
                        <select name="checkout_status" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                            <option value="">All checkout statuses</option>
                            @foreach ($checkoutStatusOptions as $option)
                                <option value="{{ $option }}" @selected(($filters['checkout_status'] ?? '') === $option)>{{ ucfirst($option) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div style="grid-column:1 / -1;display:flex;gap:10px;flex-wrap:wrap;">
                        <button class="btn primary" type="submit">Apply filters</button>
                        <a class="btn" href="{{ route('admin.finance') }}">Clear</a>
                        <a class="btn" href="{{ route('admin.finance.export', array_merge($filters, ['dataset' => 'wallets'])) }}">Export wallets CSV</a>
                        <a class="btn" href="{{ route('admin.finance.export', array_merge($filters, ['dataset' => 'payouts'])) }}">Export payouts CSV</a>
                        <a class="btn" href="{{ route('admin.finance.export', array_merge($filters, ['dataset' => 'ledger'])) }}">Export ledger CSV</a>
                        <a class="btn" href="{{ route('admin.finance.export', array_merge($filters, ['dataset' => 'checkouts'])) }}">Export checkouts CSV</a>
                    </div>
                </form>
            </section>

            <section class="card" style="margin-top:22px;">
                <h2>Founder Wallet Balances</h2>
                <div class="stack" style="margin-top: 14px;">
                    @forelse ($walletRows as $row)
                        <div class="stack-item">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                                <div>
                                    <strong>{{ $row['company_name'] }}</strong><br>
                                    {{ $row['founder_name'] }} · {{ $row['email'] }} · {{ ucfirst($row['business_model']) }}
                                </div>
                                <div class="pill">{{ str_replace('_', ' ', ucfirst($row['stripe_status'])) }}</div>
                            </div>
                            <div class="grid-2" style="margin-top: 12px;">
                                <div class="card" style="padding:14px;border-radius:16px;">
                                    <strong>{{ $row['currency'] }} {{ number_format($row['available_balance'], 2) }}</strong><br>
                                    <span class="muted">Available balance</span>
                                    <div class="muted" style="margin-top:8px;">Reserved {{ $row['currency'] }} {{ number_format($row['reserved_balance'], 2) }}</div>
                                    <div class="muted">Pending {{ $row['currency'] }} {{ number_format($row['pending_balance'], 2) }}</div>
                                </div>
                                <div class="card" style="padding:14px;border-radius:16px;">
                                    <strong>{{ $row['currency'] }} {{ number_format($row['net_earnings_total'], 2) }}</strong><br>
                                    <span class="muted">Net earnings</span>
                                    <div class="muted" style="margin-top:8px;">Gross {{ $row['currency'] }} {{ number_format($row['gross_sales_total'], 2) }}</div>
                                    <div class="muted">Refunded {{ $row['currency'] }} {{ number_format($row['refunded_sales_total'], 2) }}</div>
                                    <div class="muted">Fees {{ $row['currency'] }} {{ number_format($row['platform_fees_total'], 2) }}</div>
                                </div>
                            </div>
                            <div class="muted" style="margin-top:10px;">Payout rail: {{ $row['bank_summary'] }}</div>
                            <form method="POST" action="{{ route('admin.finance.adjustment') }}" class="grid-2" style="margin-top:12px;">
                                @csrf
                                <input type="hidden" name="founder_id" value="{{ $row['founder_id'] }}">
                                <label>
                                    <span class="muted">Adjustment type</span>
                                    <select name="entry_type" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                                        <option value="credit">Credit founder wallet</option>
                                        <option value="debit">Debit founder wallet</option>
                                    </select>
                                </label>
                                <label>
                                    <span class="muted">Amount</span>
                                    <input type="number" step="0.01" min="0.01" name="amount" placeholder="25.00" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                                </label>
                                <label>
                                    <span class="muted">Currency</span>
                                    <input type="text" name="currency" value="{{ $row['currency'] }}" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                                </label>
                                <label>
                                    <span class="muted">Reason</span>
                                    <input type="text" name="reason" placeholder="Manual finance adjustment reason" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                                </label>
                                <div style="grid-column:1 / -1;">
                                    <button class="btn primary" type="submit">Post wallet adjustment</button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="stack-item">
                            <strong>No wallet activity yet</strong><br>
                            Founder wallet balances will appear here once commerce sales begin flowing through the OS.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="grid-2" style="margin-top:22px;">
                <div class="card">
                    <h2>Payout Requests</h2>
                    <div class="stack" style="margin-top:14px;">
                        @forelse ($payoutRequests as $payout)
                            <div class="stack-item">
                                <strong>{{ $payout['company_name'] }}</strong><br>
                                {{ $payout['founder_name'] }} · {{ strtoupper($payout['currency']) }} {{ number_format((float) $payout['amount'], 2) }}
                                <div class="muted" style="margin-top:6px;">
                                    {{ ucfirst($payout['status']) }} · {{ $payout['processed_at'] ?: $payout['requested_at'] }}
                                </div>
                                <div class="muted" style="margin-top:6px;">{{ $payout['destination_summary'] }}</div>
                                @if ($payout['reference'] !== '')
                                    <div class="muted" style="margin-top:6px;">Reference: {{ $payout['reference'] }}</div>
                                @endif
                                @if ($payout['rejection_reason'] !== '')
                                    <div class="muted" style="margin-top:6px;">Reason: {{ $payout['rejection_reason'] }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No payout requests found</strong><br>
                                Founder withdrawal history will appear here once requests start flowing through the OS.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <h2>Recent Ledger Activity</h2>
                    <div class="stack" style="margin-top:14px;">
                        @forelse ($recentLedgerEntries as $entry)
                            <div class="stack-item">
                                <strong>{{ $entry['company_name'] }}</strong><br>
                                {{ $entry['founder_name'] }} · {{ strtoupper($entry['currency']) }} {{ number_format((float) $entry['amount'], 2) }}
                                <div class="muted" style="margin-top:6px;">
                                    {{ $entry['entry_type'] }} · {{ $entry['source_platform'] }}/{{ $entry['source_category'] }} · {{ $entry['status'] }}
                                </div>
                                <div class="muted" style="margin-top:6px;">
                                    Ref {{ $entry['source_reference'] ?: 'n/a' }} · {{ $entry['created_at'] }}
                                </div>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No ledger activity yet</strong><br>
                                Wallet entries will appear here when commerce payments, payouts, fees, or reversals occur.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="card" style="margin-top:22px;">
                <h2>Checkout Review Queue</h2>
                <div class="stack" style="margin-top:14px;">
                    @forelse ($checkoutRows as $checkout)
                        <div class="stack-item">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                                <div>
                                    <strong>{{ $checkout['company_name'] }}</strong><br>
                                    {{ $checkout['founder_name'] }} · {{ strtoupper($checkout['currency']) }} {{ number_format((float) $checkout['amount'], 2) }} · {{ ucfirst($checkout['checkout_status']) }}
                                    <div class="muted" style="margin-top:6px;">{{ ucfirst($checkout['platform']) }} {{ $checkout['category'] }} · {{ $checkout['offer_title'] }}</div>
                                    <div class="muted" style="margin-top:6px;">Customer {{ $checkout['customer_name'] ?: 'n/a' }}{{ $checkout['customer_email'] !== '' ? ' · ' . $checkout['customer_email'] : '' }}</div>
                                    <div class="muted" style="margin-top:6px;">Commerce ref {{ $checkout['commerce_reference'] ?: 'n/a' }} · Stripe {{ $checkout['stripe_payment_intent_id'] ?: $checkout['stripe_session_id'] }}</div>
                                </div>
                                <div class="pill">{{ $checkout['reviewed'] ? 'Reviewed' : 'Open review' }}</div>
                            </div>
                            <div class="muted" style="margin-top:10px;">Created {{ $checkout['created_at'] }}{{ $checkout['completed_at'] ? ' · Completed ' . $checkout['completed_at'] : '' }}</div>
                            @if ($checkout['reviewed'])
                                <div class="muted" style="margin-top:6px;">Reviewed by {{ $checkout['reviewed_by'] ?: 'Admin' }}{{ $checkout['reviewed_at'] ? ' · ' . $checkout['reviewed_at'] : '' }}</div>
                            @endif
                            @if ($checkout['review_note'] !== '')
                                <div class="muted" style="margin-top:6px;">Review note: {{ $checkout['review_note'] }}</div>
                            @endif
                            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
                                <a class="btn" href="{{ $checkout['commerce_url'] }}">Open founder in Commerce Control</a>
                            </div>
                            <form method="POST" action="{{ route('admin.finance.checkout.review', $checkout['id']) }}" class="grid-2" style="margin-top:12px;">
                                @csrf
                                <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                                <input type="hidden" name="payout_status" value="{{ $filters['payout_status'] ?? '' }}">
                                <input type="hidden" name="checkout_status" value="{{ $filters['checkout_status'] ?? '' }}">
                                <label>
                                    <span class="muted">Finance action</span>
                                    <select name="review_action" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                                        <option value="reviewed">Mark reviewed</option>
                                        <option value="reopen">Reopen review</option>
                                    </select>
                                </label>
                                <label>
                                    <span class="muted">Finance note</span>
                                    <input type="text" name="note" placeholder="Optional review note" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                                </label>
                                <div style="grid-column:1 / -1;">
                                    <button class="btn primary" type="submit">Save finance review</button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="stack-item">
                            <strong>No checkout review items found</strong><br>
                            Stripe disputes, refunds, and other checkout exceptions will appear here for finance handling.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
