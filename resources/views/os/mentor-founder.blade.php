@extends('os.layout')

@section('content')
    @php
        $mentor = $workspace['mentor'];
        $founder = $workspace['founder'];
        $actionPlans = $workspace['action_plans'];
        $activity = $workspace['activity'];
        $meetingPrep = $workspace['meeting_prep'];
    @endphp

    <div class="sidebar-layout">
        <aside class="sidebar-card">
            <div class="pill">Mentor Workspace</div>
            <div class="nav-group" style="margin-top: 18px;">
                <div class="nav-group-title">Mentor View</div>
                <a class="nav-item" href="/dashboard/mentor">Overview</a>
                <a class="nav-item active" href="{{ route('mentor.founders.show', $founder['id']) }}">Founder Detail</a>
                <a class="nav-item" href="/dashboard">OS Home</a>
            </div>
            <div class="nav-group">
                <div class="nav-group-title">Quick Links</div>
                <a class="nav-item" href="{{ route('dashboard.mentor') }}">Mentor Overview</a>
            </div>
        </aside>

        <div>
            <section class="hero">
                <div class="eyebrow">Founder Review</div>
                <h1>{{ $founder['company_name'] }}</h1>
                <p class="muted">{{ $founder['name'] }} · {{ $founder['business_model'] }} · {{ $founder['plan_name'] }} · Assigned to {{ $mentor->full_name }} since {{ $founder['assigned_at'] ?: 'recently' }}</p>
            </section>

            <section class="metrics">
                <div class="card metric">
                    <div class="muted">Weekly Progress</div>
                    <strong>{{ $founder['weekly_progress_percent'] }}%</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Open Tasks</div>
                    <strong>{{ $founder['open_tasks'] }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Open Milestones</div>
                    <strong>{{ $founder['open_milestones'] }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Revenue</div>
                    <strong>USD {{ number_format($founder['gross_revenue'], 0) }}</strong>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Execution Summary</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Weekly focus</strong><br>
                            {{ $founder['weekly_focus'] }}
                        </div>
                        <div class="stack-item">
                            <strong>Next meeting</strong><br>
                            {{ $founder['next_meeting_at'] ?: 'Not synced yet' }}
                        </div>
                        <div class="stack-item">
                            <strong>Founder brief</strong><br>
                            {{ $founder['company_brief'] ?: 'No company brief saved yet.' }}
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>Atlas Context</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Primary growth goal</strong><br>
                            {{ $founder['primary_growth_goal'] ?: 'Not synced yet' }}
                        </div>
                        <div class="stack-item">
                            <strong>Brand voice</strong><br>
                            {{ $founder['brand_voice'] ?: 'Not synced yet' }}
                        </div>
                        <div class="stack-item">
                            <strong>Known blockers</strong><br>
                            {{ $founder['known_blockers'] ?: 'Not synced yet' }}
                        </div>
                    </div>
                </div>
            </section>

            @if (session('success'))
                <section class="card" style="border-color: rgba(44, 122, 87, 0.25); background: rgba(44, 122, 87, 0.06); margin-top: 22px;">
                    <p class="muted">{{ session('success') }}</p>
                </section>
            @endif

            @if (session('error'))
                <section class="card" style="border-color: rgba(179, 34, 83, 0.25); background: rgba(179, 34, 83, 0.06); margin-top: 22px;">
                    <p class="muted">{{ session('error') }}</p>
                </section>
            @endif

            <section class="card" style="margin-top: 22px;">
                <h2>Mentor Notes</h2>
                <p class="muted">Keep running guidance and session context attached to this founder inside the OS.</p>
                <form method="POST" action="{{ route('mentor.founders.notes', $founder['id']) }}" style="margin-top: 14px;">
                    @csrf
                    <textarea name="notes" rows="6" style="width:100%;padding:12px 14px;border-radius:14px;border:1px solid var(--line);background:#fff;resize:vertical;">{{ $founder['notes'] }}</textarea>
                    <div class="cta-row">
                        <button class="btn primary" type="submit">Save Mentor Notes</button>
                    </div>
                </form>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Meeting Prep</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Agenda</strong><br>
                            @if (!empty($meetingPrep['agenda']))
                                {{ implode(' ', $meetingPrep['agenda']) }}
                            @else
                                No prep agenda has been generated yet.
                            @endif
                        </div>
                        <div class="stack-item">
                            <strong>Execution summary</strong><br>
                            @if (!empty($meetingPrep['execution_summary']))
                                {{ implode(' · ', $meetingPrep['execution_summary']) }}
                            @else
                                Execution summary will appear here once the founder has more synced OS activity.
                            @endif
                        </div>
                        <div class="stack-item">
                            <strong>Recommended follow-ups</strong><br>
                            @if (!empty($meetingPrep['follow_ups']))
                                {{ implode(' ', $meetingPrep['follow_ups']) }}
                            @else
                                No follow-ups suggested yet.
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>Action Queue</h2>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($actionPlans as $action)
                            <div class="stack-item">
                                <strong>{{ $action['title'] }}</strong><br>
                                {{ $action['description'] }}
                                <div class="muted" style="margin-top: 6px;">{{ $action['platform'] }} · {{ ucfirst($action['status']) }}</div>
                                @if ($action['platform'] === 'LMS')
                                    <div class="cta-row">
                                        <form method="POST" action="{{ route('mentor.founders.actions.status', [$founder['id'], $action['id'] ?? 0]) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $action['completed'] ? 'pending' : 'completed' }}">
                                            <button class="btn" type="submit">{{ $action['completed'] ? 'Reopen Task' : 'Mark Complete' }}</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No tracked action plans yet</strong><br>
                                Founder tasks and milestones will appear here as the OS and LMS sync execution records.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <h2>Commerce Summary</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Products</strong><br>
                            {{ $founder['product_count'] }}
                        </div>
                        <div class="stack-item">
                            <strong>Services</strong><br>
                            {{ $founder['service_count'] }}
                        </div>
                        <div class="stack-item">
                            <strong>Orders</strong><br>
                            {{ $founder['order_count'] }}
                        </div>
                        <div class="stack-item">
                            <strong>Bookings</strong><br>
                            {{ $founder['booking_count'] }}
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>LMS Activity</h2>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($activity['lms'] as $item)
                            <div class="stack-item">{{ $item['message'] }}</div>
                        @empty
                            <div class="stack-item">No LMS activity synced yet.</div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <h2>Atlas Activity</h2>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($activity['atlas'] as $item)
                            <div class="stack-item">{{ $item['message'] }}</div>
                        @empty
                            <div class="stack-item">No Atlas activity synced yet.</div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="card" style="margin-top: 22px;">
                <h2>Commerce Activity</h2>
                <div class="stack" style="margin-top: 14px;">
                    @forelse ($activity['commerce'] as $item)
                        <div class="stack-item">{{ $item['message'] }}</div>
                    @empty
                        <div class="stack-item">No Bazaar or Servio activity synced yet.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
