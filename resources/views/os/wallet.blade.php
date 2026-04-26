@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .wallet-shell { min-height:100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 240px; background:#f8f5ee; }
        .wallet-sidebar, .wallet-rightbar { background:rgba(255,252,247,0.8); border-color:var(--line); border-style:solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .wallet-rightbar { border-width:0 0 0 1px; background:rgba(255,251,246,0.9); }
        .wallet-sidebar-inner, .wallet-rightbar-inner { padding:22px 18px; }
        .wallet-brand { display:inline-block; margin-bottom:24px; }
        .wallet-brand img { width:168px; height:auto; display:block; }
        .wallet-nav { display:grid; gap:6px; }
        .wallet-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .wallet-nav-item.active { background:#ece6db; }
        .wallet-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .wallet-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .wallet-user { display:flex; align-items:center; gap:10px; }
        .wallet-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .wallet-main { padding:26px 28px 24px; }
        .wallet-main-inner { max-width:860px; margin:0 auto; }
        .wallet-main h1 { font-size:clamp(2rem, 3vw, 3rem); letter-spacing:-0.02em; margin-bottom:6px; }
        .wallet-main p { color:var(--muted); margin-bottom:24px; }
        .wallet-banner { border-radius:16px; padding:14px 16px; border:1px solid rgba(220,207,191,0.8); background:rgba(255,255,255,0.9); margin-bottom:14px; }
        .wallet-banner.success { border-color:rgba(44,122,87,0.26); background:rgba(226,245,236,0.9); }
        .wallet-banner.error { border-color:rgba(179,34,83,0.22); background:rgba(255,241,246,0.92); }
        .wallet-metrics { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:22px; }
        .wallet-metric, .wallet-card, .rail-item { background:rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:16px 18px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .wallet-metric strong { display:block; font-size:1.55rem; margin-top:6px; }
        .wallet-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; }
        .wallet-stack { display:grid; gap:10px; }
        .wallet-filter-grid { display:grid; gap:10px; grid-template-columns:repeat(2, minmax(0,1fr)); }
        .wallet-field label { display:block; font-size:0.92rem; font-weight:600; color:var(--ink); }
        .wallet-field input, .wallet-field select { width:100%; margin-top:6px; border:1px solid rgba(220,207,191,0.9); background:#fff; border-radius:12px; padding:10px 12px; font:inherit; color:var(--ink); }
        .wallet-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .wallet-pill, .wallet-btn { display:inline-block; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:600; border:0; cursor:pointer; font:inherit; }
        .wallet-pill { background:#f0ece4; color:#5d554a; }
        .wallet-btn { background:linear-gradient(90deg,#8e1c74,#ff2c35); color:#fff; }
        .wallet-row-header { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap; }
        .wallet-positive { color:#21643a; }
        .wallet-negative { color:#b32253; }
        .wallet-muted { color:var(--muted); }
        @media (max-width:1240px) { .wallet-shell { grid-template-columns:220px 1fr; } .wallet-rightbar { display:none; } }
        @media (max-width:900px) { .wallet-shell { grid-template-columns:1fr; } .wallet-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .wallet-sidebar-footer { display:none; } .wallet-main { padding:20px 16px 24px; } .wallet-grid, .wallet-metrics, .wallet-filter-grid { grid-template-columns:1fr; } }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $workspace = $walletWorkspace;
        $summary = $workspace['summary'];
        $filters = $workspace['filters'];
        $ledgerEntries = $workspace['ledger_entries'];
        $payoutRequests = $workspace['payout_requests'];
        $entryTypeOptions = $workspace['entry_type_options'];
        $entryStatusOptions = $workspace['entry_status_options'];
        $payoutStatusOptions = $workspace['payout_status_options'];
    @endphp

    <div class="wallet-shell">
        <aside class="wallet-sidebar">
            <div class="wallet-sidebar-inner">
                <a class="wallet-brand" href="/dashboard/founder"><img src="/brand/hatchers-ai-logo.png" alt="Hatchers AI"></a>
                <nav class="wallet-nav">
                    <a class="wallet-nav-item" href="/dashboard/founder"><span class="wallet-nav-icon">⌂</span><span>Home</span></a>
                    <a class="wallet-nav-item" href="{{ route('founder.commerce') }}"><span class="wallet-nav-icon">⌁</span><span>Commerce</span></a>
                    <a class="wallet-nav-item active" href="{{ route('founder.commerce.wallet') }}"><span class="wallet-nav-icon">$</span><span>Wallet</span></a>
                    <a class="wallet-nav-item" href="{{ route('founder.commerce.orders') }}"><span class="wallet-nav-icon">▤</span><span>Orders</span></a>
                    <a class="wallet-nav-item" href="{{ route('founder.commerce.bookings') }}"><span class="wallet-nav-icon">◫</span><span>Bookings</span></a>
                    <a class="wallet-nav-item" href="{{ route('website') }}"><span class="wallet-nav-icon">◧</span><span>Website</span></a>
                    <a class="wallet-nav-item" href="{{ route('founder.settings') }}"><span class="wallet-nav-icon">⚙</span><span>Settings</span></a>
                </nav>
            </div>
            <div class="wallet-sidebar-footer">
                <div class="wallet-user">
                    <div class="wallet-avatar">{{ strtoupper(substr($founder->full_name, 0, 1)) }}</div>
                    <div>{{ $founder->full_name }}</div>
                </div>
                <form method="POST" action="/logout" style="margin:0;">@csrf<button class="wallet-nav-icon" type="submit" style="border:0;background:transparent;cursor:pointer;">↘</button></form>
            </div>
        </aside>

        <main class="wallet-main">
            <div class="wallet-main-inner">
                <h1>Wallet</h1>
                <p>Track what the founder has earned, what Hatchers retained as fees, what was refunded, and what is already in payout flow.</p>

                @if (session('success'))
                    <div class="wallet-banner success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="wallet-banner error">{{ session('error') }}</div>
                @endif

                <section class="wallet-metrics">
                    <div class="wallet-metric"><div class="wallet-muted">Available</div><strong>{{ $summary['currency'] }} {{ number_format((float) $summary['available_balance'], 2) }}</strong></div>
                    <div class="wallet-metric"><div class="wallet-muted">Pending</div><strong>{{ $summary['currency'] }} {{ number_format((float) $summary['pending_balance'], 2) }}</strong></div>
                    <div class="wallet-metric"><div class="wallet-muted">Reserved</div><strong>{{ $summary['currency'] }} {{ number_format((float) $summary['reserved_balance'], 2) }}</strong></div>
                    <div class="wallet-metric"><div class="wallet-muted">Net earnings</div><strong>{{ $summary['currency'] }} {{ number_format((float) $summary['net_earnings_total'], 2) }}</strong></div>
                </section>

                <section class="wallet-grid">
                    <div class="wallet-card">
                        <h2 style="margin-bottom:12px;">Money Flow</h2>
                        <div class="wallet-stack">
                            <div><strong>Gross sales</strong><br><span class="wallet-muted">{{ $summary['currency'] }} {{ number_format((float) $summary['gross_sales_total'], 2) }}</span></div>
                            <div><strong>Refunded sales</strong><br><span class="wallet-muted">{{ $summary['currency'] }} {{ number_format((float) $summary['refunded_sales_total'], 2) }}</span></div>
                            <div><strong>Platform fees</strong><br><span class="wallet-muted">{{ $summary['currency'] }} {{ number_format((float) $summary['platform_fees_total'], 2) }}</span></div>
                            <div><strong>Minimum withdrawal</strong><br><span class="wallet-muted">USD {{ number_format((float) $summary['minimum_payout_amount'], 2) }}</span></div>
                        </div>
                    </div>

                    <div class="wallet-card">
                        <h2 style="margin-bottom:12px;">Payout Readiness</h2>
                        <div class="wallet-stack">
                            <div><strong>Stripe payouts</strong><br><span class="wallet-muted">
                                @if ($payoutAccount && $payoutAccount->stripe_payouts_enabled)
                                    Connected and ready
                                @elseif ($payoutAccount && $payoutAccount->stripe_account_id)
                                    {{ ucfirst((string) ($payoutAccount->stripe_onboarding_status ?: 'pending')) }}
                                @else
                                    Not connected yet
                                @endif
                            </span></div>
                            <div><strong>Bank destination</strong><br><span class="wallet-muted">{{ $payoutAccount ? (($payoutAccount->bank_name ?? 'Bank') . ' · ' . ($payoutAccount->iban ?: $payoutAccount->account_number ?: 'Saved')) : 'No payout account saved yet' }}</span></div>
                            <div class="wallet-actions">
                                <a class="wallet-pill" href="{{ route('founder.commerce') }}">Open payout controls</a>
                                @if ($payoutAccount && $payoutAccount->stripe_account_id)
                                    <a class="wallet-btn" href="{{ route('founder.commerce.payout-account.connect') }}">Resume Stripe onboarding</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                <section class="wallet-card" style="margin-top:12px;">
                    <h2 style="margin-bottom:12px;">Filter Wallet Activity</h2>
                    <form method="GET" action="{{ route('founder.commerce.wallet') }}" class="wallet-filter-grid">
                        <div class="wallet-field">
                            <label for="entry_type">Entry type</label>
                            <select id="entry_type" name="entry_type">
                                @foreach ($entryTypeOptions as $option)
                                    <option value="{{ $option }}" @selected($filters['entry_type'] === $option)>{{ $option === 'all' ? 'All entry types' : ucfirst($option) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="wallet-field">
                            <label for="entry_status">Entry status</label>
                            <select id="entry_status" name="entry_status">
                                @foreach ($entryStatusOptions as $option)
                                    <option value="{{ $option }}" @selected($filters['entry_status'] === $option)>{{ $option === 'all' ? 'All ledger statuses' : ucfirst($option) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="wallet-field">
                            <label for="payout_status">Payout status</label>
                            <select id="payout_status" name="payout_status">
                                @foreach ($payoutStatusOptions as $option)
                                    <option value="{{ $option }}" @selected($filters['payout_status'] === $option)>{{ $option === 'all' ? 'All payout statuses' : ucfirst($option) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="wallet-field">
                            <label for="q">Search</label>
                            <input id="q" name="q" type="text" value="{{ $filters['q'] }}" placeholder="Reference, platform, or destination">
                        </div>
                        <div class="wallet-actions" style="grid-column:1 / -1;">
                            <button class="wallet-btn" type="submit">Apply filters</button>
                            <a class="wallet-pill" href="{{ route('founder.commerce.wallet') }}">Clear</a>
                            <a class="wallet-pill" href="{{ route('founder.commerce.wallet.export', array_merge($filters, ['dataset' => 'ledger'])) }}">Export ledger CSV</a>
                            <a class="wallet-pill" href="{{ route('founder.commerce.wallet.export', array_merge($filters, ['dataset' => 'payouts'])) }}">Export withdrawals CSV</a>
                        </div>
                    </form>
                </section>

                <section class="wallet-card" style="margin-top:12px;">
                    <h2 style="margin-bottom:12px;">Ledger History</h2>
                    <div class="wallet-stack">
                        @forelse ($ledgerEntries as $entry)
                            <div class="rail-item">
                                <div class="wallet-row-header">
                                    <div>
                                        <strong>{{ $entry['headline'] }}</strong><br>
                                        <span class="wallet-muted">{{ $entry['source_platform'] !== '' ? ucfirst($entry['source_platform']) . ' · ' : '' }}{{ $entry['status_label'] }} · {{ $entry['created_at'] ?: 'Pending timestamp' }}</span>
                                    </div>
                                    <div class="{{ $entry['entry_type'] === 'credit' ? 'wallet-positive' : 'wallet-negative' }}">
                                        {{ $entry['entry_type'] === 'credit' ? '+' : '-' }}{{ $entry['currency'] }} {{ $entry['amount_display'] }}
                                    </div>
                                </div>
                                <div class="wallet-muted" style="margin-top:8px;">{{ $entry['source_category_label'] }}</div>
                                @if ($entry['note'] !== '')
                                    <div class="wallet-muted" style="margin-top:6px;">{{ $entry['note'] }}</div>
                                @endif
                                @if ($entry['available_at'])
                                    <div class="wallet-muted" style="margin-top:6px;">Available at {{ $entry['available_at'] }}</div>
                                @endif
                                <div class="wallet-actions" style="margin-top:10px;">
                                    <a class="wallet-pill" href="{{ $entry['related_url'] }}">{{ $entry['related_label'] }}</a>
                                </div>
                            </div>
                        @empty
                            <div class="rail-item">
                                <strong>No wallet ledger activity yet</strong><br>
                                <span class="wallet-muted">Sales, fees, refunds, and withdrawals will appear here once the founder starts taking payments through the OS.</span>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="wallet-card" style="margin-top:12px;">
                    <h2 style="margin-bottom:12px;">Withdrawal History</h2>
                    <div class="wallet-stack">
                        @forelse ($payoutRequests as $request)
                            <div class="rail-item">
                                <div class="wallet-row-header">
                                    <div>
                                        <strong>{{ strtoupper($request['currency']) }} {{ $request['amount_display'] }}</strong><br>
                                        <span class="wallet-muted">{{ $request['status_label'] }} · {{ $request['requested_at'] ?: 'Pending timestamp' }}</span>
                                    </div>
                                    <div class="wallet-muted">{{ $request['destination_summary'] }}</div>
                                </div>
                                @if ($request['processed_at'])
                                    <div class="wallet-muted" style="margin-top:8px;">Processed at {{ $request['processed_at'] }}</div>
                                @endif
                                @if ($request['reference'] !== '')
                                    <div class="wallet-muted" style="margin-top:6px;">Reference: {{ $request['reference'] }}</div>
                                @endif
                                @if ($request['notes'] !== '')
                                    <div class="wallet-muted" style="margin-top:6px;">Founder note: {{ $request['notes'] }}</div>
                                @endif
                                @if ($request['rejection_reason'] !== '')
                                    <div class="wallet-muted" style="margin-top:6px;">Rejection reason: {{ $request['rejection_reason'] }}</div>
                                @endif
                                <div class="wallet-actions" style="margin-top:10px;">
                                    <a class="wallet-pill" href="{{ $request['related_url'] }}">{{ $request['related_label'] }}</a>
                                </div>
                            </div>
                        @empty
                            <div class="rail-item">
                                <strong>No withdrawal requests yet</strong><br>
                                <span class="wallet-muted">Founder withdrawal requests will appear here once the wallet balance reaches the minimum and a request is submitted.</span>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </main>

        <aside class="wallet-rightbar">
            <div class="wallet-rightbar-inner">
                <h3 style="font-size:0.83rem;letter-spacing:0.06em;text-transform:uppercase;color:var(--muted);margin-bottom:12px;">Wallet Focus</h3>
                <div class="wallet-stack">
                    <div class="rail-item">
                        <strong>Available now</strong><br>
                        <span class="wallet-muted">{{ $summary['currency'] }} {{ number_format((float) $summary['available_balance'], 2) }}</span>
                    </div>
                    <div class="rail-item">
                        <strong>Reserved for payouts</strong><br>
                        <span class="wallet-muted">{{ $summary['currency'] }} {{ number_format((float) $summary['reserved_balance'], 2) }}</span>
                    </div>
                    <div class="rail-item">
                        <strong>Latest payout status</strong><br>
                        <span class="wallet-muted">
                            @if (!empty($payoutRequests))
                                {{ $payoutRequests[0]['status_label'] }} · {{ strtoupper($payoutRequests[0]['currency']) }} {{ $payoutRequests[0]['amount_display'] }}
                            @else
                                No withdrawal submitted yet
                            @endif
                        </span>
                    </div>
                    <div class="rail-item">
                        <strong>Next step</strong><br>
                        <span class="wallet-muted">
                            @if (!$payoutAccount)
                                Save a payout account in Commerce before requesting a withdrawal.
                            @elseif (($summary['available_balance'] ?? 0) < ($summary['minimum_payout_amount'] ?? 50))
                                Keep selling until the founder wallet reaches the withdrawal minimum.
                            @elseif (!$payoutAccount->stripe_payouts_enabled)
                                Finish Stripe payout onboarding for automatic withdrawals.
                            @else
                                The founder can request a withdrawal from the Commerce workspace.
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </aside>
    </div>
@endsection
