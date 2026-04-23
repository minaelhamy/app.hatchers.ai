@extends('os.layout')

@section('content')
    @php
        $admin = $report['admin'];
        $filters = $report['filters'];
        $filterOptions = $report['filter_options'];
        $metrics = $report['metrics'];
        $health = $report['health'];
        $subscribers = $report['subscribers'];
    @endphp

    <div class="sidebar-layout">
        <aside class="sidebar-card">
            <div class="pill">System Admin</div>
            <div class="nav-group" style="margin-top: 18px;">
                <div class="nav-group-title">Control Center</div>
                <a class="nav-item" href="/dashboard/admin">Overview</a>
                <a class="nav-item active" href="{{ route('admin.subscribers') }}">Subscribers</a>
                <a class="nav-item" href="/admin/control">Founder Operations</a>
                <a class="nav-item" href="{{ route('admin.system-access') }}">System Access</a>
                <a class="nav-item" href="{{ route('admin.identity') }}">Identity</a>
                <a class="nav-item" href="{{ route('admin.modules') }}">Module Monitoring</a>
                <a class="nav-item" href="{{ route('admin.support') }}">Support Center</a>
                <a class="nav-item" href="/dashboard">OS Home</a>
            </div>
        </aside>

        <div>
            <section class="hero">
                <div class="eyebrow">Subscriber Reporting</div>
                <h1>Track subscriber growth, health, and execution from the OS.</h1>
                <p class="muted">Welcome back, {{ $admin->full_name }}. This view is the OS-native reporting layer for subscriber growth, billing, progress, and founder health.</p>
            </section>

            <section class="metrics" style="margin-bottom: 22px;">
                <div class="card metric"><div class="muted">Filtered subscribers</div><strong>{{ $metrics['filtered_subscribers'] }}</strong></div>
                <div class="card metric"><div class="muted">Active billing</div><strong>{{ $metrics['active_subscribers'] }}</strong></div>
                <div class="card metric"><div class="muted">Trialing</div><strong>{{ $metrics['trialing_subscribers'] }}</strong></div>
                <div class="card metric"><div class="muted">Avg progress</div><strong>{{ $metrics['avg_weekly_progress'] }}%</strong></div>
                <div class="card metric"><div class="muted">Live websites</div><strong>{{ $metrics['live_websites'] }}</strong></div>
                <div class="card metric"><div class="muted">Mentor coverage</div><strong>{{ $metrics['mentor_coverage'] }}</strong></div>
                <div class="card metric"><div class="muted">New 7 days</div><strong>{{ $metrics['new_last_7_days'] }}</strong></div>
                <div class="card metric"><div class="muted">New 30 days</div><strong>{{ $metrics['new_last_30_days'] }}</strong></div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Filter Subscribers</h2>
                    <form method="GET" action="{{ route('admin.subscribers') }}" class="stack" style="margin-top: 14px;">
                        <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search founder, email, or company" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        <div class="grid-2">
                            <select name="plan_code" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="">All plans</option>
                                @foreach ($filterOptions['plan_codes'] as $option)
                                    <option value="{{ $option }}" @selected($filters['plan_code'] === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            <select name="billing_status" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="">All billing states</option>
                                @foreach ($filterOptions['billing_statuses'] as $option)
                                    <option value="{{ $option }}" @selected($filters['billing_status'] === $option)>{{ ucfirst($option) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid-2">
                            <select name="status" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="">All founder states</option>
                                @foreach ($filterOptions['statuses'] as $option)
                                    <option value="{{ $option }}" @selected($filters['status'] === $option)>{{ ucfirst($option) }}</option>
                                @endforeach
                            </select>
                            <select name="business_model" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="">All business models</option>
                                @foreach ($filterOptions['business_models'] as $option)
                                    <option value="{{ $option }}" @selected($filters['business_model'] === $option)>{{ ucfirst($option) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Apply Filters</button>
                            <a class="btn" href="{{ route('admin.subscribers') }}">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h2>Subscriber Health</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item"><strong>On track</strong><br>{{ $health['on_track'] }} founders are active and above 60% weekly progress.</div>
                        <div class="stack-item"><strong>Watchlist</strong><br>{{ $health['watchlist'] }} founders need closer monitoring.</div>
                        <div class="stack-item"><strong>At risk</strong><br>{{ $health['at_risk'] }} founders are blocked, paused, or below healthy execution velocity.</div>
                        <div class="stack-item"><strong>Revenue tracked</strong><br>USD {{ number_format($metrics['gross_revenue'], 0) }}</div>
                    </div>
                </div>
            </section>

            <section class="card" style="margin-top: 22px;">
                <h2>Subscriber List</h2>
                <div class="stack" style="margin-top: 14px;">
                    @forelse ($subscribers as $subscriber)
                        <div class="stack-item">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:start;flex-wrap:wrap;">
                                <div>
                                    <strong>{{ $subscriber['company_name'] }}</strong><br>
                                    {{ $subscriber['name'] }} · {{ $subscriber['email'] }} · {{ ucfirst($subscriber['business_model']) }}
                                </div>
                                <div class="pill">{{ ucfirst($subscriber['billing_status']) }}</div>
                            </div>
                            <div class="muted" style="margin-top: 6px;">
                                {{ $subscriber['plan_name'] }} · {{ ucfirst($subscriber['status']) }} · {{ $subscriber['weekly_progress_percent'] }}% weekly progress · {{ $subscriber['open_tasks'] }} open tasks · Mentor {{ $subscriber['mentor_name'] }}
                            </div>
                            <div class="muted" style="margin-top: 6px;">
                                Website {{ str_replace('_', ' ', ucfirst($subscriber['website_status'])) }} · Orders {{ $subscriber['orders'] }} · Bookings {{ $subscriber['bookings'] }} · USD {{ number_format($subscriber['gross_revenue'], 0) }} · Joined {{ $subscriber['created_at'] }}
                            </div>
                        </div>
                    @empty
                        <div class="stack-item">
                            <strong>No subscribers match these filters</strong><br>
                            Try widening the search or clearing one of the filter controls.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
