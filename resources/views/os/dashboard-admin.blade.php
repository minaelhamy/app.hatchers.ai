@extends('os.layout')

@section('content')
    @php
        $admin = $dashboard['admin'];
        $metrics = $dashboard['metrics'];
        $moduleHealth = $dashboard['module_health'];
        $recentSubscribers = $dashboard['recent_subscribers'];
        $launches = $dashboard['role_launches'];
        $launchCards = $launchCards ?? [];
    @endphp
    <div class="sidebar-layout">
        <aside class="sidebar-card">
            <div class="pill">System Admin</div>
            <div class="nav-group" style="margin-top: 18px;">
                <div class="nav-group-title">Control Center</div>
                <a class="nav-item active" href="/dashboard/admin">Overview</a>
                <a class="nav-item" href="{{ route('admin.subscribers') }}">Subscribers</a>
                <a class="nav-item" href="/admin/control">Founder Operations</a>
                <a class="nav-item" href="{{ route('admin.system-access') }}">System Access</a>
                <a class="nav-item" href="{{ route('admin.identity') }}">Identity</a>
                <a class="nav-item" href="{{ route('admin.commerce') }}">Commerce Control</a>
                <a class="nav-item" href="{{ route('admin.modules') }}">Module Monitoring</a>
                <a class="nav-item" href="{{ route('admin.support') }}">Support Center</a>
                <a class="nav-item" href="/dashboard">OS Home</a>
            </div>
        </aside>

        <div>
            <section class="hero">
                <div class="eyebrow">Admin Dashboard</div>
                <h1>Run the whole Hatchers platform from one operating system.</h1>
                <p class="muted">Welcome back, {{ $admin->full_name }}. This view is the first OS-level control center for subscribers, mentors, module health, and cross-platform operations.</p>
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
                <div class="card metric">
                    <div class="muted">Founders</div>
                    <strong>{{ $metrics['founders'] }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Mentors</div>
                    <strong>{{ $metrics['mentors'] }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Subscribers</div>
                    <strong>{{ $metrics['subscribers'] }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Live Websites</div>
                    <strong>{{ $metrics['live_websites'] }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Healthy Modules</div>
                    <strong>{{ $metrics['healthy_modules'] }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Stale Modules</div>
                    <strong>{{ $metrics['stale_modules'] }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Offline Modules</div>
                    <strong>{{ $metrics['offline_modules'] }}</strong>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Admin Actions</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Founder operations</strong><br>
                            Assign mentors and update subscription state directly from the OS.
                            <div style="margin-top: 10px;">
                                <a class="pill" href="/admin/control">Open Admin Control</a>
                                <a class="pill" href="{{ route('admin.subscribers') }}">Open Subscriber Reporting</a>
                                <a class="pill" href="{{ route('admin.system-access') }}">Open System Access</a>
                                <a class="pill" href="{{ route('admin.identity') }}">Open Identity Workspace</a>
                                <a class="pill" href="{{ route('admin.commerce') }}">Open Commerce Control</a>
                                <a class="pill" href="{{ route('admin.modules') }}">Open Module Monitoring</a>
                                <a class="pill" href="{{ route('admin.support') }}">Open Support Center</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>System Health</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Active subscribers</strong><br>
                            {{ $metrics['active_subscribers'] }} active or onboarding accounts are currently inside the OS lifecycle.
                        </div>
                        <div class="stack-item">
                            <strong>Active mentor assignments</strong><br>
                            {{ $metrics['active_mentor_assignments'] }} founder-to-mentor relationships are currently marked active.
                        </div>
                        <div class="stack-item">
                            <strong>Gross revenue tracked</strong><br>
                            USD {{ number_format($metrics['gross_revenue'], 0) }}
                        </div>
                        <div class="stack-item">
                            <strong>Admin seats</strong><br>
                            {{ $metrics['admins'] }} admin users can now use the OS shell as a central control point.
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>Module Readiness</h2>
                    <div class="cta-row" style="margin-top: 12px;">
                        <form method="POST" action="{{ route('admin.control.retry-sync') }}">
                            @csrf
                            <button class="btn primary" type="submit" name="target" value="all" style="cursor: pointer;">Retry All Supported Modules</button>
                        </form>
                    </div>
                    <div class="stack" style="margin-top: 14px;">
                        @foreach ($moduleHealth as $module)
                            <div class="stack-item">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                    <strong>{{ $module['module'] }}</strong>
                                    <span class="pill" style="
                                        @if ($module['status_tone'] === 'success')
                                            background: rgba(44, 122, 87, 0.1); color: var(--success); border-color: rgba(44, 122, 87, 0.18);
                                        @elseif ($module['status_tone'] === 'warning')
                                            background: rgba(180, 83, 9, 0.08); color: #b45309; border-color: rgba(180, 83, 9, 0.18);
                                        @else
                                            background: rgba(179, 34, 83, 0.08); color: var(--rose); border-color: rgba(179, 34, 83, 0.18);
                                        @endif
                                    ">{{ $module['status'] }}</span>
                                </div>
                                <div class="muted" style="margin-top: 8px;">{{ $module['status_reason'] }}</div>
                                <div style="margin-top: 10px;">
                                    {{ $module['synced_founders'] }} synced founders · {{ $module['missing_founders'] }} missing · {{ $module['coverage_percent'] }}% coverage
                                </div>
                                <div class="muted" style="margin-top: 6px;">
                                    {{ $module['avg_readiness'] }}% average readiness · Last synced {{ $module['last_synced_at'] ?: 'not yet' }}
                                </div>
                                @if (in_array(strtolower($module['module']), ['atlas', 'bazaar', 'servio'], true))
                                    <div class="cta-row" style="margin-top: 10px;">
                                        <form method="POST" action="{{ route('admin.control.retry-sync') }}">
                                            @csrf
                                            <button class="btn" type="submit" name="target" value="{{ strtolower($module['module']) }}" style="cursor: pointer;">Retry {{ $module['module'] }} Sync</button>
                                        </form>
                                    </div>
                                @else
                                    <div class="muted" style="margin-top: 10px;">LMS retry remains manual until the LMS sync write path is standardized.</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="card" style="margin-top: 22px;">
                <h2>Recent Subscribers</h2>
                <div class="stack" style="margin-top: 14px;">
                    @forelse ($recentSubscribers as $subscriber)
                        <div class="stack-item">
                            <strong>{{ $subscriber['company_name'] }}</strong><br>
                            {{ $subscriber['name'] }} · {{ $subscriber['email'] }} · {{ $subscriber['business_model'] }}
                            <div class="muted" style="margin-top: 6px;">
                                {{ $subscriber['plan_name'] }} · {{ $subscriber['billing_status'] }} · {{ $subscriber['weekly_progress_percent'] }}% weekly progress · USD {{ number_format($subscriber['gross_revenue'], 0) }}
                            </div>
                        </div>
                    @empty
                        <div class="stack-item">
                            <strong>No subscribers yet</strong><br>
                            Once founders enter through Hatchers OS, they will appear here with their cross-tool status.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
