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
                <a class="nav-item" href="/dashboard">OS Home</a>
            </div>
            <div class="nav-group">
                <div class="nav-group-title">Connected Tools</div>
                @foreach ($launches as $launch)
                    <a class="nav-item" href="{{ $launch['url'] }}" target="_blank" rel="noreferrer">{{ $launch['label'] }}</a>
                @endforeach
            </div>
        </aside>

        <div>
            <section class="hero">
                <div class="eyebrow">Admin Dashboard</div>
                <h1>Run the whole Hatchers platform from one operating system.</h1>
                <p class="muted">Welcome back, {{ $admin->full_name }}. This view is the first OS-level control center for subscribers, mentors, module health, and cross-platform operations.</p>
            </section>

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
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Open Tools</h2>
                    <div class="stack" style="margin-top: 14px;">
                        @foreach ($launchCards as $launch)
                            <div class="stack-item">
                                <strong>{{ $launch['label'] }}</strong><br>
                                {{ $launch['description'] }}
                                <div style="margin-top: 10px;">
                                    <a class="pill" href="/workspace/launch/{{ strtolower($launch['module']) }}">Open {{ $launch['label'] }}</a>
                                </div>
                            </div>
                        @endforeach
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
                    <div class="stack" style="margin-top: 14px;">
                        @foreach ($moduleHealth as $module)
                            <div class="stack-item">
                                <strong>{{ $module['module'] }}</strong><br>
                                {{ $module['synced_founders'] }} synced founders · {{ $module['avg_readiness'] }}% average readiness
                                <div class="muted" style="margin-top: 6px;">Last synced {{ $module['last_synced_at'] ?: 'not yet' }}</div>
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
