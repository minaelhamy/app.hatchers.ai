@extends('os.layout')

@section('content')
    @php
        $admin = $workspace['admin'];
        $metrics = $workspace['metrics'];
        $moduleHealth = $workspace['module_health'];
        $exceptions = $workspace['exceptions'];
        $recentAudits = $workspace['recent_audits'];
        $recentSubscribers = $workspace['recent_subscribers'];
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
                <a class="nav-item active" href="{{ route('admin.modules') }}">Module Monitoring</a>
                <a class="nav-item" href="{{ route('admin.support') }}">Support Center</a>
                <a class="nav-item" href="/dashboard">OS Home</a>
            </div>
        </aside>

        <div>
            <section class="hero">
                <div class="eyebrow">Module Monitoring</div>
                <h1>Monitor sync trust, failures, and recovery from the OS.</h1>
                <p class="muted">Welcome back, {{ $admin->full_name }}. This is the central reliability surface for LMS, Atlas, Bazaar, and Servio health inside Hatchers Ai Business OS.</p>
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

            <section class="metrics" style="margin-bottom: 22px;">
                <div class="card metric"><div class="muted">Healthy modules</div><strong>{{ $metrics['healthy_modules'] }}</strong></div>
                <div class="card metric"><div class="muted">Stale modules</div><strong>{{ $metrics['stale_modules'] }}</strong></div>
                <div class="card metric"><div class="muted">Offline modules</div><strong>{{ $metrics['offline_modules'] }}</strong></div>
                <div class="card metric"><div class="muted">Founders</div><strong>{{ $metrics['founders'] }}</strong></div>
                <div class="card metric"><div class="muted">Active subscribers</div><strong>{{ $metrics['active_subscribers'] }}</strong></div>
            </section>

            <section class="card">
                <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap;">
                    <div>
                        <h2>Module Health</h2>
                        <p class="muted">Each card below reflects freshness, founder coverage, and operational trust.</p>
                    </div>
                    <form method="POST" action="{{ route('admin.control.retry-sync') }}">
                        @csrf
                        <button class="btn primary" type="submit" name="target" value="all">Retry All Supported Modules</button>
                    </form>
                </div>

                <div class="stack" style="margin-top: 14px;">
                    @foreach ($moduleHealth as $module)
                        <div class="stack-item">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:start;flex-wrap:wrap;">
                                <div>
                                    <strong>{{ $module['module'] }}</strong><br>
                                    <span class="muted">{{ $module['status_reason'] }}</span>
                                </div>
                                <div class="pill" style="
                                    @if ($module['status_tone'] === 'success')
                                        background: rgba(44, 122, 87, 0.1); color: var(--success); border-color: rgba(44, 122, 87, 0.18);
                                    @elseif ($module['status_tone'] === 'warning')
                                        background: rgba(180, 83, 9, 0.08); color: #b45309; border-color: rgba(180, 83, 9, 0.18);
                                    @else
                                        background: rgba(179, 34, 83, 0.08); color: var(--rose); border-color: rgba(179, 34, 83, 0.18);
                                    @endif
                                ">{{ $module['status'] }}</div>
                            </div>
                            <div class="grid-3" style="margin-top: 12px;">
                                <div class="stack-item" style="background: rgba(240, 231, 218, 0.35);"><strong>{{ $module['synced_founders'] }}</strong><br>Synced founders</div>
                                <div class="stack-item" style="background: rgba(240, 231, 218, 0.35);"><strong>{{ $module['missing_founders'] }}</strong><br>Missing founders</div>
                                <div class="stack-item" style="background: rgba(240, 231, 218, 0.35);"><strong>{{ $module['coverage_percent'] }}%</strong><br>Coverage</div>
                            </div>
                            <div class="muted" style="margin-top: 10px;">
                                {{ $module['avg_readiness'] }}% average readiness
                                @if ($module['last_synced_at'])
                                    · Last synced {{ $module['last_synced_at'] }}
                                @else
                                    · No successful snapshot yet
                                @endif
                            </div>
                            <div class="cta-row">
                                @if (in_array(strtolower($module['module']), ['atlas', 'bazaar', 'servio'], true))
                                    <form method="POST" action="{{ route('admin.control.retry-sync') }}">
                                        @csrf
                                        <button class="btn" type="submit" name="target" value="{{ strtolower($module['module']) }}">Retry {{ $module['module'] }}</button>
                                    </form>
                                @else
                                    <div class="pill">LMS retry still requires manual bridge execution</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Exception Queue</h2>
                    <p class="muted">These are the failures the OS has recorded so they do not disappear into server logs.</p>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($exceptions as $exception)
                            <div class="stack-item">
                                <div style="display:flex;justify-content:space-between;gap:12px;align-items:start;flex-wrap:wrap;">
                                    <div>
                                        <strong>{{ $exception['module'] }} · {{ $exception['operation'] }}</strong><br>
                                        <span class="muted">{{ $exception['message'] }}</span>
                                    </div>
                                    <div class="pill">{{ ucfirst($exception['status']) }}</div>
                                </div>
                                <div class="muted" style="margin-top: 8px;">
                                    {{ $exception['created_at'] }}@if($exception['founder_name']) · {{ $exception['founder_name'] }}@endif
                                </div>
                                @if ($exception['status'] !== 'resolved')
                                    <div class="cta-row">
                                        <form method="POST" action="{{ route('admin.control.exceptions.resolve', $exception['id']) }}">
                                            @csrf
                                            <button class="btn primary" type="submit">Resolve</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No tracked exceptions right now</strong><br>
                                The OS is not currently holding any unresolved module issues in the exception queue.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <h2>Recent Reliability Audit</h2>
                    <p class="muted">Recent OS-level admin and recovery actions appear here so the support team can reconstruct what changed.</p>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($recentAudits as $audit)
                            <div class="stack-item">
                                <strong>{{ $audit['summary'] }}</strong><br>
                                <span class="muted">{{ $audit['actor_name'] }} · {{ $audit['created_at'] }}</span>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No recent audit entries</strong><br>
                                Once OS operations happen here, those events will appear in this audit feed.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="card" style="margin-top: 22px;">
                <h2>Recently Synced Subscribers</h2>
                <div class="stack" style="margin-top: 14px;">
                    @forelse ($recentSubscribers as $subscriber)
                        <div class="stack-item">
                            <strong>{{ $subscriber['company_name'] }}</strong><br>
                            {{ $subscriber['name'] }} · {{ $subscriber['plan_name'] }} · {{ $subscriber['billing_status'] }}
                            <div class="muted" style="margin-top: 6px;">
                                {{ $subscriber['weekly_progress_percent'] }}% weekly progress · USD {{ number_format($subscriber['gross_revenue'], 0) }} · {{ $subscriber['created_at'] }}
                            </div>
                        </div>
                    @empty
                        <div class="stack-item">
                            <strong>No recently synced subscribers</strong><br>
                            As founder records flow through the OS, they will appear here with their current health context.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
