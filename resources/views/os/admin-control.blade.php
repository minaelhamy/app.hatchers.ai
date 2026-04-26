@extends('os.layout')

@section('content')
    @php
        $admin = $workspace['admin'];
        $founders = $workspace['founders'];
        $mentors = $workspace['mentors'];
        $billingStatuses = $workspace['billing_statuses'];
        $planOptions = $workspace['plan_options'];
        $statusOptions = $workspace['status_options'];
        $unassignedFounderCount = $workspace['unassigned_founder_count'];
        $recentAudits = $workspace['recent_audits'];
        $exceptions = $workspace['exceptions'];
    @endphp
    <div class="sidebar-layout">
        <aside class="sidebar-card">
            <div class="pill">Admin Control</div>
            <div class="nav-group" style="margin-top: 18px;">
                <div class="nav-group-title">Control Center</div>
                <a class="nav-item" href="/dashboard/admin">Overview</a>
                <a class="nav-item active" href="/admin/control">Founder Operations</a>
                <a class="nav-item" href="{{ route('admin.subscribers') }}">Subscribers</a>
                <a class="nav-item" href="{{ route('admin.system-access') }}">System Access</a>
                <a class="nav-item" href="{{ route('admin.identity') }}">Identity</a>
                <a class="nav-item" href="{{ route('admin.commerce') }}">Commerce Control</a>
                <a class="nav-item" href="{{ route('admin.finance') }}">Finance Control</a>
                <a class="nav-item" href="{{ route('admin.modules') }}">Module Monitoring</a>
                <a class="nav-item" href="{{ route('admin.support') }}">Support Center</a>
            </div>
        </aside>

        <div>
            <section class="hero">
                <div class="eyebrow">Admin Operations</div>
                <h1>Manage founders, mentors, and subscriptions from the OS.</h1>
                <p class="muted">This is the operating window for subscriber health, mentor balancing, founder status, and cross-tool sync across Bazaar, Servio, Atlas, and LMS from {{ $admin->full_name }}’s Hatchers OS workspace.</p>
            </section>

            @if (session('success'))
                <section class="card" style="border-color: rgba(44, 122, 87, 0.25); background: rgba(44, 122, 87, 0.06);">
                    <h3 style="color: var(--success);">Action completed</h3>
                    <p class="muted" style="margin-top: 8px;">{{ session('success') }}</p>
                </section>
            @endif

            @if (session('error'))
                <section class="card" style="border-color: rgba(179, 34, 83, 0.25); background: rgba(179, 34, 83, 0.06);">
                    <h3 style="color: var(--rose);">Something needs attention</h3>
                    <p class="muted" style="margin-top: 8px;">{{ session('error') }}</p>
                </section>
            @endif

            @if ($errors->any())
                <section class="card" style="border-color: rgba(154, 107, 27, 0.28); background: rgba(154, 107, 27, 0.08);">
                    <h3 style="color: var(--warning);">A few fields still need adjustment</h3>
                    <div class="stack" style="margin-top: 12px;">
                        @foreach ($errors->all() as $error)
                            <div class="stack-item">{{ $error }}</div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="metrics" style="margin-bottom: 22px;">
                <div class="card metric">
                    <div class="muted">Founders</div>
                    <strong>{{ count($founders) }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Mentors</div>
                    <strong>{{ count($mentors) }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Unassigned founders</div>
                    <strong>{{ $unassignedFounderCount }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Mentor load</div>
                    <strong>{{ collect($mentors)->sum('assigned_founder_count') }}</strong>
                </div>
            </section>

            <section class="card">
                <h2>Founder Operations</h2>
                <p class="muted">Update subscriber status, company profile, mentor ownership, billing, and sync state without leaving the OS.</p>
                <div class="stack" style="margin-top: 14px;">
                    @forelse ($founders as $founder)
                        <div class="stack-item">
                            <div style="display: flex; justify-content: space-between; gap: 12px; align-items: start; flex-wrap: wrap;">
                                <div>
                                    <strong>{{ $founder['company_name'] }}</strong><br>
                                    {{ $founder['name'] }} · {{ $founder['email'] }} · {{ ucfirst($founder['business_model']) }}
                                </div>
                                <div class="pill" style="background: {{ $founder['status'] === 'blocked' ? 'rgba(179, 34, 83, 0.12)' : ($founder['status'] === 'paused' ? 'rgba(154, 107, 27, 0.12)' : 'rgba(44, 122, 87, 0.12)') }};">
                                    {{ ucfirst($founder['status']) }}
                                </div>
                            </div>
                            <div class="muted" style="margin-top: 6px;">
                                Stage {{ ucfirst($founder['stage']) }} · Website {{ str_replace('_', ' ', ucfirst($founder['website_status'])) }} · {{ $founder['weekly_progress_percent'] }}% weekly progress · {{ $founder['open_tasks'] }} open tasks · USD {{ number_format($founder['gross_revenue'], 0) }} · Plan {{ $founder['plan_name'] }}
                            </div>

                            <div class="grid-2" style="margin-top: 14px;">
                                <form method="POST" action="{{ route('admin.control.founder') }}" class="card" style="padding: 16px; border-radius: 18px;">
                                    @csrf
                                    <input type="hidden" name="founder_id" value="{{ $founder['id'] }}">
                                    <div class="muted" style="margin-bottom: 10px;">Founder profile</div>
                                    <div class="grid-2">
                                        <input type="text" name="full_name" value="{{ $founder['name'] }}" placeholder="Founder name" style="width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid var(--line); background: #fff;">
                                        <input type="email" name="email" value="{{ $founder['email'] }}" placeholder="Email" style="width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid var(--line); background: #fff;">
                                        <input type="text" name="phone" value="{{ $founder['phone'] }}" placeholder="Phone" style="width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid var(--line); background: #fff;">
                                        <input type="text" name="country" value="{{ $founder['country'] }}" placeholder="Country code" style="width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid var(--line); background: #fff;">
                                    </div>
                                    <div class="grid-2" style="margin-top: 10px;">
                                        <select name="status" style="width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid var(--line); background: #fff;">
                                            @foreach ($statusOptions as $status)
                                                <option value="{{ $status }}" @selected($founder['status'] === $status)>{{ ucfirst($status) }}</option>
                                            @endforeach
                                        </select>
                                        <select name="business_model" style="width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid var(--line); background: #fff;">
                                            <option value="product" @selected($founder['business_model'] === 'product')>Product</option>
                                            <option value="service" @selected($founder['business_model'] === 'service')>Service</option>
                                            <option value="hybrid" @selected($founder['business_model'] === 'hybrid')>Hybrid</option>
                                        </select>
                                    </div>
                                    <div class="grid-2" style="margin-top: 10px;">
                                        <input type="text" name="company_name" value="{{ $founder['company_name'] }}" placeholder="Company name" style="width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid var(--line); background: #fff;">
                                        <input type="text" name="industry" value="{{ $founder['industry'] }}" placeholder="Industry" style="width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid var(--line); background: #fff;">
                                    </div>
                                    <div class="grid-2" style="margin-top: 10px;">
                                        <select name="stage" style="width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid var(--line); background: #fff;">
                                            <option value="idea" @selected($founder['stage'] === 'idea')>Idea</option>
                                            <option value="launching" @selected($founder['stage'] === 'launching')>Launching</option>
                                            <option value="operating" @selected($founder['stage'] === 'operating')>Operating</option>
                                            <option value="scaling" @selected($founder['stage'] === 'scaling')>Scaling</option>
                                        </select>
                                        <select name="website_status" style="width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid var(--line); background: #fff;">
                                            <option value="not_started" @selected($founder['website_status'] === 'not_started')>Website not started</option>
                                            <option value="in_progress" @selected($founder['website_status'] === 'in_progress')>Website in progress</option>
                                            <option value="live" @selected($founder['website_status'] === 'live')>Website live</option>
                                        </select>
                                    </div>
                                    <textarea name="company_brief" rows="4" placeholder="Company brief" style="width: 100%; margin-top: 10px; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff; resize: vertical;">{{ $founder['company_brief'] }}</textarea>
                                    <div class="cta-row">
                                        <button class="btn primary" type="submit" style="cursor: pointer;">Save Founder Profile</button>
                                    </div>
                                </form>

                                <div class="stack">
                                    <form method="POST" action="{{ route('admin.control.mentor') }}" class="card" style="padding: 16px; border-radius: 18px;">
                                        @csrf
                                        <input type="hidden" name="founder_id" value="{{ $founder['id'] }}">
                                        <div class="muted" style="margin-bottom: 8px;">Mentor assignment</div>
                                        <select name="mentor_id" style="width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid var(--line); background: #fff;">
                                            <option value="">No mentor</option>
                                            @foreach ($mentors as $mentor)
                                                <option value="{{ $mentor['id'] }}" @selected((int) $founder['assigned_mentor_id'] === (int) $mentor['id'])>
                                                    {{ $mentor['full_name'] }} · {{ $mentor['assigned_founder_count'] }} founders
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="cta-row">
                                            <button class="btn primary" type="submit" style="cursor: pointer;">Save Mentor</button>
                                        </div>
                                        <div class="muted" style="margin-top: 8px;">Current: {{ $founder['assigned_mentor_name'] ?: 'Unassigned' }}</div>
                                    </form>

                                    <form method="POST" action="{{ route('admin.control.subscription') }}" class="card" style="padding: 16px; border-radius: 18px;">
                                        @csrf
                                        <input type="hidden" name="founder_id" value="{{ $founder['id'] }}">
                                        <div class="muted" style="margin-bottom: 8px;">Subscription state</div>
                                        <select name="plan_code" id="plan-code-{{ $founder['id'] }}" style="width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid var(--line); background: #fff;" onchange="(function(el){var selected=el.options[el.selectedIndex];document.getElementById('plan-name-{{ $founder['id'] }}').value=selected.dataset.name;document.getElementById('plan-amount-{{ $founder['id'] }}').value=selected.dataset.amount;})(this)">
                                            @foreach ($planOptions as $plan)
                                                <option value="{{ $plan['code'] }}" data-name="{{ $plan['name'] }}" data-amount="{{ $plan['amount'] }}" @selected($founder['plan_code'] === $plan['code'])>{{ $plan['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="plan_name" id="plan-name-{{ $founder['id'] }}" value="{{ $founder['plan_name'] }}">
                                        <div class="grid-2" style="margin-top: 10px;">
                                            <select name="billing_status" style="width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid var(--line); background: #fff;">
                                                @foreach ($billingStatuses as $status)
                                                    <option value="{{ $status }}" @selected($founder['billing_status'] === $status)>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                            <input type="number" step="0.01" min="0" name="amount" id="plan-amount-{{ $founder['id'] }}" value="{{ $founder['amount'] }}" style="width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid var(--line); background: #fff;" placeholder="Amount">
                                        </div>
                                        <div class="cta-row">
                                            <button class="btn primary" type="submit" style="cursor: pointer;">Save Subscription</button>
                                        </div>
                                    </form>

                                    <form method="POST" action="{{ route('admin.control.sync') }}" class="card" style="padding: 16px; border-radius: 18px;">
                                        @csrf
                                        <input type="hidden" name="founder_id" value="{{ $founder['id'] }}">
                                        <div class="muted" style="margin-bottom: 8px;">Cross-tool sync</div>
                                        <p class="muted">Push this founder’s latest OS profile into Bazaar, Servio, and Atlas.</p>
                                        <div class="cta-row">
                                            <button class="btn" type="submit" name="target" value="atlas" style="cursor: pointer;">Sync Atlas</button>
                                            <button class="btn" type="submit" name="target" value="bazaar" style="cursor: pointer;">Sync Bazaar</button>
                                            <button class="btn" type="submit" name="target" value="servio" style="cursor: pointer;">Sync Servio</button>
                                            <button class="btn primary" type="submit" name="target" value="all" style="cursor: pointer;">Sync All Tools</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="stack-item">
                            <strong>No founders yet</strong><br>
                            As founders sync into the OS, you will be able to manage status, profile, subscription state, and cross-tool sync from here.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="card">
                <h2>Mentor Portfolio</h2>
                <p class="muted">Review mentor load, see who is over capacity, and rebalance assignments using the founder controls above.</p>
                <div class="stack" style="margin-top: 14px;">
                    @forelse ($mentors as $mentor)
                        <div class="stack-item">
                            <div style="display: flex; justify-content: space-between; gap: 12px; align-items: start; flex-wrap: wrap;">
                                <div>
                                    <strong>{{ $mentor['full_name'] }}</strong><br>
                                    {{ $mentor['email'] }} · {{ $mentor['username'] ?: 'No username yet' }}
                                </div>
                                <div class="pill">{{ $mentor['assigned_founder_count'] }} founders</div>
                            </div>
                            <div class="muted" style="margin-top: 6px;">
                                {{ $mentor['avg_progress'] }}% average weekly progress · USD {{ number_format($mentor['gross_revenue'], 0) }} founder revenue tracked
                            </div>

                            @if (!empty($mentor['founders']))
                                <div class="stack" style="margin-top: 12px;">
                                    @foreach ($mentor['founders'] as $mentorFounder)
                                        <div class="stack-item" style="background: rgba(240, 231, 218, 0.35);">
                                            <strong>{{ $mentorFounder['company_name'] }}</strong><br>
                                            {{ $mentorFounder['name'] }} · {{ $mentorFounder['weekly_progress_percent'] }}% progress · USD {{ number_format($mentorFounder['gross_revenue'], 0) }}
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="muted" style="margin-top: 10px;">No active founder assignments yet.</div>
                            @endif
                        </div>
                    @empty
                        <div class="stack-item">
                            <strong>No mentors synced yet</strong><br>
                            As LMS mentor identities sync into Hatchers OS, you’ll be able to manage portfolio balance from this workspace.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Admin Audit Log</h2>
                    <p class="muted">Every meaningful admin operation in the OS should leave a trace here.</p>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($recentAudits as $audit)
                            <div class="stack-item">
                                <strong>{{ $audit['summary'] }}</strong><br>
                                <span class="muted">{{ $audit['actor_name'] }} · {{ $audit['actor_role'] }} · {{ $audit['created_at'] }}</span>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No audit entries yet</strong><br>
                                As admins make changes from Hatchers Ai Business OS, those actions will be tracked here.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <h2>Exception Queue</h2>
                    <p class="muted">Failed sync and operational issues should surface here instead of getting lost in server logs.</p>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($exceptions as $exception)
                            <div class="stack-item">
                                <div style="display:flex;justify-content:space-between;gap:12px;align-items:start;flex-wrap:wrap;">
                                    <div>
                                        <strong>{{ $exception['module'] }} · {{ $exception['operation'] }}</strong><br>
                                        <span class="muted">{{ $exception['created_at'] }} @if($exception['founder_name']) · {{ $exception['founder_name'] }} @endif</span>
                                    </div>
                                    <div class="pill" style="background: {{ $exception['status'] === 'resolved' ? 'rgba(44, 122, 87, 0.12)' : 'rgba(179, 34, 83, 0.08)' }};">
                                        {{ ucfirst($exception['status']) }}
                                    </div>
                                </div>
                                <div class="muted" style="margin-top:8px;">{{ $exception['message'] }}</div>
                                @if ($exception['status'] !== 'resolved')
                                    <div class="cta-row">
                                        <form method="POST" action="{{ route('admin.control.exceptions.resolve', $exception['id']) }}">
                                            @csrf
                                            <button class="btn primary" type="submit" style="cursor:pointer;">Resolve</button>
                                        </form>
                                    </div>
                                @elseif ($exception['resolved_at'])
                                    <div class="muted" style="margin-top:8px;">Resolved {{ $exception['resolved_at'] }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No open exceptions</strong><br>
                                OS sync and admin operations are currently not reporting tracked failures.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
