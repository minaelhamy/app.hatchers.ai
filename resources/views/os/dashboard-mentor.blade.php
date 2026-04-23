@extends('os.layout')

@section('content')
    @php
        $mentor = $dashboard['mentor'];
        $metrics = $dashboard['metrics'];
        $founders = $dashboard['founders'];
        $launches = $dashboard['launches'];
        $launchCards = $launchCards ?? [];
    @endphp
    <div class="sidebar-layout">
        <aside class="sidebar-card">
            <div class="pill">Mentor Workspace</div>
            <div class="nav-group" style="margin-top: 18px;">
                <div class="nav-group-title">Mentor View</div>
                <a class="nav-item active" href="/dashboard/mentor">Overview</a>
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
                <div class="eyebrow">Mentor Dashboard</div>
                <h1>Mentor your founders from the same operating system.</h1>
                <p class="muted">Welcome back, {{ $mentor->full_name }}. This OS view mirrors the LMS mentor workflow while bringing in founder business progress from Atlas, Bazaar, and Servio.</p>
            </section>

            <section class="metrics">
                <div class="card metric">
                    <div class="muted">Assigned Founders</div>
                    <strong>{{ $metrics['assigned_founders'] }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Open Tasks</div>
                    <strong>{{ $metrics['open_tasks'] }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Open Milestones</div>
                    <strong>{{ $metrics['open_milestones'] }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Average Progress</div>
                    <strong>{{ $metrics['avg_progress'] }}%</strong>
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
                                    <a class="pill" href="{{ route('mentor.legacy-tools') }}">Legacy access</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card">
                    <h2>Mentor Summary</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Assigned founders</strong><br>
                            {{ $metrics['assigned_founders'] }}
                        </div>
                        <div class="stack-item">
                            <strong>Tracked revenue</strong><br>
                            USD {{ number_format($metrics['gross_revenue'], 0) }}
                        </div>
                    </div>
                </div>
            </section>

            <section class="card" style="margin-top: 22px;">
                <h2>Founder Portfolio</h2>
                <div class="stack" style="margin-top: 14px;">
                    @forelse ($founders as $founder)
                        <div class="stack-item">
                            <strong>{{ $founder['company_name'] }}</strong><br>
                            {{ $founder['name'] }} · {{ $founder['business_model'] }}
                            <div class="muted" style="margin-top: 6px;">
                                {{ $founder['weekly_progress_percent'] }}% progress · {{ $founder['open_tasks'] }} open tasks · {{ $founder['open_milestones'] }} open milestones · USD {{ number_format($founder['gross_revenue'], 0) }}
                            </div>
                            <div style="margin-top: 8px;">
                                <strong>Weekly focus</strong><br>
                                {{ $founder['weekly_focus'] }}
                            </div>
                            @if ($founder['primary_growth_goal'])
                                <div class="muted" style="margin-top: 6px;">Growth goal: {{ $founder['primary_growth_goal'] }}</div>
                            @endif
                            <div class="muted" style="margin-top: 6px;">Next meeting: {{ $founder['next_meeting_at'] ?: 'Not synced yet' }}</div>
                            @if (!empty($founder['id']))
                                <div style="margin-top: 10px;">
                                    <a class="pill" href="{{ route('mentor.founders.show', $founder['id']) }}">Open founder review</a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="stack-item">
                            <strong>No founder assignments yet</strong><br>
                            As mentor assignments sync into the OS, your founder roster and weekly execution view will appear here.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
