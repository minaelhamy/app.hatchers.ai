@extends('os.layout')

@section('content')
    @php
        $admin = $workspace['admin'];
        $metrics = $workspace['metrics'];
        $urgentSubscribers = $workspace['urgent_subscribers'];
        $staleModules = $workspace['stale_modules'];
        $exceptions = $workspace['exceptions'];
        $recentAudits = $workspace['recent_audits'];
        $mailDiagnostics = $workspace['mail_diagnostics'] ?? [];
        $openPayoutRequests = $workspace['open_payout_requests'] ?? [];
        $payoutRequests = $workspace['payout_requests'] ?? [];
    @endphp

    <div class="sidebar-layout">
        <aside class="sidebar-card">
            <div class="pill">System Admin</div>
            <div class="nav-group" style="margin-top: 18px;">
                <div class="nav-group-title">Control Center</div>
                <a class="nav-item" href="/dashboard/admin">Overview</a>
                <a class="nav-item" href="{{ route('admin.subscribers') }}">Subscribers</a>
                <a class="nav-item" href="/admin/control">Founder Operations</a>
                <a class="nav-item" href="{{ route('admin.system-access') }}">System Access</a>
                <a class="nav-item" href="{{ route('admin.identity') }}">Identity</a>
                <a class="nav-item" href="{{ route('admin.commerce') }}">Commerce Control</a>
                <a class="nav-item" href="{{ route('admin.finance') }}">Finance Control</a>
                <a class="nav-item" href="{{ route('admin.modules') }}">Module Monitoring</a>
                <a class="nav-item active" href="{{ route('admin.support') }}">Support Center</a>
                <a class="nav-item" href="/dashboard">OS Home</a>
            </div>
        </aside>

        <div>
            <section class="hero">
                <div class="eyebrow">Support Center</div>
                <h1>Run support operations from the OS.</h1>
                <p class="muted">Welcome back, {{ $admin->full_name }}. This workspace pulls founder risk, stale module trust, and open exception handling into one operational surface.</p>
            </section>

            @if (session('success'))
                <section class="card" style="border-color: rgba(44, 122, 87, 0.25); background: rgba(44, 122, 87, 0.06); margin-bottom: 18px;">
                    <h3 style="color: var(--success);">Action completed</h3>
                    <p class="muted" style="margin-top: 8px;">{{ session('success') }}</p>
                </section>
            @endif

            @if (session('error'))
                <section class="card" style="border-color: rgba(179, 34, 83, 0.25); background: rgba(179, 34, 83, 0.06); margin-bottom: 18px;">
                    <h3 style="color: var(--rose);">Action needs attention</h3>
                    <p class="muted" style="margin-top: 8px;">{{ session('error') }}</p>
                </section>
            @endif

            <section class="metrics" style="margin-bottom: 22px;">
                <div class="card metric"><div class="muted">Urgent founders</div><strong>{{ $metrics['urgent_founders'] }}</strong></div>
                <div class="card metric"><div class="muted">Open exceptions</div><strong>{{ $metrics['open_exceptions'] }}</strong></div>
                <div class="card metric"><div class="muted">Stale modules</div><strong>{{ $metrics['stale_modules'] }}</strong></div>
                <div class="card metric"><div class="muted">Watchlist founders</div><strong>{{ $metrics['watchlist_founders'] }}</strong></div>
                <div class="card metric"><div class="muted">Open payouts</div><strong>{{ $metrics['open_payout_requests'] ?? 0 }}</strong></div>
            </section>

            <section class="grid-2">
                <div class="card">
                    <h2>Founder Watchlist</h2>
                    <p class="muted">These founders need support attention because they are blocked, off-track, or billing-risky.</p>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($urgentSubscribers as $subscriber)
                            <div class="stack-item">
                                <strong>{{ $subscriber['company_name'] }}</strong><br>
                                {{ $subscriber['name'] }} · {{ ucfirst($subscriber['status']) }} · {{ ucfirst($subscriber['billing_status']) }}
                                <div class="muted" style="margin-top: 6px;">
                                    {{ $subscriber['weekly_progress_percent'] }}% weekly progress · {{ $subscriber['mentor_name'] }} · USD {{ number_format($subscriber['gross_revenue'], 0) }}
                                </div>
                                <div class="cta-row" style="margin-top: 10px;">
                                    <a class="btn" href="{{ route('admin.control') }}">Open founder operations</a>
                                    <a class="btn" href="{{ route('admin.subscribers', ['search' => $subscriber['email']]) }}">Open subscriber record</a>
                                </div>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No urgent founder issues right now</strong><br>
                                The OS is not currently flagging a founder support watchlist.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <h2>Reliability Queue</h2>
                    <p class="muted">Use this queue to work stale trust issues and unresolved cross-tool failures without leaving the OS.</p>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($staleModules as $module)
                            <div class="stack-item">
                                <strong>{{ $module['module'] }} · {{ $module['status'] }}</strong><br>
                                {{ $module['status_reason'] }}
                                <div class="muted" style="margin-top: 6px;">
                                    {{ $module['coverage_percent'] }}% coverage · {{ $module['synced_founders'] }} synced · {{ $module['missing_founders'] }} missing
                                </div>
                                <div class="cta-row" style="margin-top: 10px;">
                                    <a class="btn" href="{{ route('admin.modules') }}">Open module monitoring</a>
                                </div>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>All modules look healthy</strong><br>
                                No stale or offline module trust signals are currently blocking support work.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Payout Queue</h2>
                    <p class="muted">Approve founder withdrawal requests after bank transfer, or reject them to restore the founder wallet balance.</p>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($openPayoutRequests as $payout)
                            <div class="stack-item">
                                <strong>{{ $payout['company_name'] }}</strong><br>
                                {{ $payout['founder_name'] }} · {{ strtoupper($payout['currency']) }} {{ number_format((float) $payout['amount'], 2) }}
                                <div class="muted" style="margin-top: 6px;">
                                    {{ ucfirst($payout['status']) }} · {{ $payout['requested_at'] }} · {{ $payout['destination_summary'] }}
                                </div>
                                @if ($payout['notes'] !== '')
                                    <div class="muted" style="margin-top: 6px;">Founder note: {{ $payout['notes'] }}</div>
                                @endif
                                <div class="cta-row" style="margin-top: 12px; flex-wrap: wrap;">
                                    <form method="POST" action="{{ route('admin.support.payouts.approve', $payout['id']) }}" style="display:flex;gap:10px;flex-wrap:wrap;">
                                        @csrf
                                        <input type="text" name="reference" placeholder="Bank transfer reference" style="padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;min-width:220px;">
                                        <button class="btn primary" type="submit">Mark paid</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.support.payouts.reject', $payout['id']) }}" style="display:flex;gap:10px;flex-wrap:wrap;">
                                        @csrf
                                        <input type="text" name="reason" placeholder="Reason for rejection" style="padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;min-width:220px;">
                                        <button class="btn" type="submit">Reject and restore wallet</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No payout requests waiting</strong><br>
                                Founder withdrawals will appear here once they request an amount above the OS minimum.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <h2>Mail Operations</h2>
                    <p class="muted">Use this to confirm that Hatchers Ai Business OS can send founder verification and support emails before testing the live founder flow.</p>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>SMTP status</strong><br>
                            <span class="muted">
                                {{ !empty($mailDiagnostics['configured']) ? 'Configured' : 'Missing required values' }} ·
                                {{ $mailDiagnostics['mailer'] ?? 'smtp' }} ·
                                {{ $mailDiagnostics['host'] ?? 'host missing' }}:{{ $mailDiagnostics['port'] ?? 'port missing' }}
                            </span>
                            <div class="muted" style="margin-top: 6px;">
                                {{ $mailDiagnostics['from_address'] ?? 'From address missing' }} · {{ $mailDiagnostics['encryption'] ?? 'encryption missing' }}
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.support.test-mail') }}" class="stack-item">
                            @csrf
                            <strong>Send test mail</strong>
                            <div class="muted" style="margin-top: 6px;">This sends a direct OS test email using the current SMTP settings.</div>
                            <input type="email" name="email" placeholder="ops@hatchers.ai" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;margin-top:10px;">
                            <div class="cta-row" style="margin-top: 12px;">
                                <button class="btn primary" type="submit">Send test mail</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <h2>Open Exception Queue</h2>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($exceptions as $exception)
                            <div class="stack-item">
                                <strong>{{ $exception['module'] }} · {{ $exception['operation'] }}</strong><br>
                                {{ $exception['message'] }}
                                <div class="muted" style="margin-top: 6px;">
                                    {{ $exception['created_at'] }}@if($exception['founder_name']) · {{ $exception['founder_name'] }}@endif
                                </div>
                                <div class="cta-row" style="margin-top: 10px;">
                                    <form method="POST" action="{{ route('admin.control.exceptions.resolve', $exception['id']) }}">
                                        @csrf
                                        <button class="btn primary" type="submit">Resolve exception</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No unresolved exceptions</strong><br>
                                Support does not currently have any open OS exception items to clear.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <h2>Recent Support Actions</h2>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($recentAudits as $audit)
                            <div class="stack-item">
                                <strong>{{ $audit['summary'] }}</strong><br>
                                <span class="muted">{{ $audit['actor_name'] }} · {{ $audit['created_at'] }}</span>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No recent support actions</strong><br>
                                Once support and recovery work happens in the OS, it will appear here.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <h2>Recent Payout Decisions</h2>
                    <div class="stack" style="margin-top: 14px;">
                        @php($recentPayoutHistory = collect($payoutRequests)->reject(fn ($row) => in_array($row['status'], ['pending', 'processing'], true))->take(8))
                        @forelse ($recentPayoutHistory as $payout)
                            <div class="stack-item">
                                <strong>{{ $payout['company_name'] }}</strong><br>
                                {{ $payout['founder_name'] }} · {{ strtoupper($payout['currency']) }} {{ number_format((float) $payout['amount'], 2) }}
                                <div class="muted" style="margin-top: 6px;">
                                    {{ ucfirst($payout['status']) }} · {{ $payout['processed_at'] ?: $payout['requested_at'] }}
                                </div>
                                @if ($payout['reference'] !== '')
                                    <div class="muted" style="margin-top: 6px;">Reference: {{ $payout['reference'] }}</div>
                                @endif
                                @if ($payout['rejection_reason'] !== '')
                                    <div class="muted" style="margin-top: 6px;">Reason: {{ $payout['rejection_reason'] }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No payout decisions yet</strong><br>
                                Approved and rejected payout requests will appear here for support visibility.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
