@extends('os.layout')

@section('content')
    @php
        $admin = $workspace['admin'];
        $mentors = $workspace['mentors'];
        $admins = $workspace['admins'];
        $mentorPermissionOptions = $workspace['mentor_permission_options'];
        $adminPermissionOptions = $workspace['admin_permission_options'];
        $rebalanceRecommendations = $workspace['rebalance_recommendations'];
        $founderRebalancePool = $workspace['founder_rebalance_pool'];
        $summary = $workspace['summary'];
    @endphp

    <div class="sidebar-layout">
        <aside class="sidebar-card">
            <div class="pill">System Admin</div>
            <div class="nav-group" style="margin-top: 18px;">
                <div class="nav-group-title">Control Center</div>
                <a class="nav-item" href="/dashboard/admin">Overview</a>
                <a class="nav-item" href="{{ route('admin.subscribers') }}">Subscribers</a>
                <a class="nav-item" href="/admin/control">Founder Operations</a>
                <a class="nav-item active" href="{{ route('admin.system-access') }}">System Access</a>
                <a class="nav-item" href="{{ route('admin.identity') }}">Identity</a>
                <a class="nav-item" href="{{ route('admin.commerce') }}">Commerce Control</a>
                <a class="nav-item" href="{{ route('admin.finance') }}">Finance Control</a>
                <a class="nav-item" href="{{ route('admin.modules') }}">Module Monitoring</a>
                <a class="nav-item" href="{{ route('admin.support') }}">Support Center</a>
                <a class="nav-item" href="/dashboard">OS Home</a>
            </div>
        </aside>

        <div>
            <section class="hero">
                <div class="eyebrow">System Access</div>
                <h1>Manage mentor and admin access from one OS workspace.</h1>
                <p class="muted">Welcome back, {{ $admin->full_name }}. This workspace is where we control who can operate the system, what they can do, and how mentor load should be rebalanced.</p>
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

            @if ($errors->any())
                <section class="card" style="border-color: rgba(154, 107, 27, 0.28); background: rgba(154, 107, 27, 0.08); margin-bottom: 18px;">
                    <h3 style="color: var(--warning);">A few fields still need adjustment</h3>
                    <div class="stack" style="margin-top: 12px;">
                        @foreach ($errors->all() as $error)
                            <div class="stack-item">{{ $error }}</div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="metrics" style="margin-bottom: 22px;">
                <div class="card metric"><div class="muted">Mentors</div><strong>{{ $summary['mentor_count'] }}</strong></div>
                <div class="card metric"><div class="muted">Admins</div><strong>{{ $summary['admin_count'] }}</strong></div>
                <div class="card metric"><div class="muted">Founder rebalance pool</div><strong>{{ $summary['founder_pool_count'] }}</strong></div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Rebalance Recommendations</h2>
                    <p class="muted">These recommendations use current founder load to point out where the OS sees uneven mentor capacity.</p>
                    <div class="stack" style="margin-top: 14px;">
                        @foreach ($rebalanceRecommendations as $recommendation)
                            <div class="stack-item">
                                <strong>{{ $recommendation['mentor_name'] }}</strong><br>
                                <span class="muted">{{ $recommendation['message'] }}</span>
                                @if (!empty($recommendation['recommended_target']))
                                    <div class="muted" style="margin-top: 6px;">
                                        Current load {{ $recommendation['current_load'] }}
                                        @if (!is_null($recommendation['target_load']))
                                            · Suggested target {{ $recommendation['recommended_target'] }} ({{ $recommendation['target_load'] }})
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card">
                    <h2>Quick Rebalance</h2>
                    <p class="muted">Use the same assignment engine the founder operations page uses, but from a mentor-load oriented workflow.</p>
                    <form method="POST" action="{{ route('admin.control.mentor') }}" class="stack" style="margin-top: 14px;">
                        @csrf
                        <select name="founder_id" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="">Choose founder</option>
                            @foreach ($founderRebalancePool as $founder)
                                <option value="{{ $founder['id'] }}">
                                    {{ $founder['company_name'] }} · {{ $founder['full_name'] }} · {{ $founder['mentor_name'] }} · {{ $founder['weekly_progress_percent'] }}%
                                </option>
                            @endforeach
                        </select>
                        <select name="mentor_id" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="">Unassign mentor</option>
                            @foreach ($mentors as $mentor)
                                <option value="{{ $mentor['id'] }}">
                                    {{ $mentor['full_name'] }} · {{ $mentor['active_assignment_count'] }} founders
                                </option>
                            @endforeach
                        </select>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Apply Rebalance</button>
                            <a class="btn" href="/admin/control">Open full founder operations</a>
                        </div>
                    </form>
                </div>
            </section>

            <section class="card" style="margin-top: 22px;">
                <h2>Mentor Profiles And Permissions</h2>
                <p class="muted">Mentor access stays deliberately narrow here so the OS can stay the safe daily workspace while LMS remains the program engine underneath.</p>
                <div class="stack" style="margin-top: 14px;">
                    @forelse ($mentors as $mentor)
                        <form method="POST" action="{{ route('admin.control.mentor-profile') }}" class="stack-item">
                            @csrf
                            <input type="hidden" name="mentor_id" value="{{ $mentor['id'] }}">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:start;flex-wrap:wrap;">
                                <div>
                                    <strong>{{ $mentor['full_name'] }}</strong><br>
                                    {{ $mentor['email'] }} · {{ $mentor['username'] ?: 'No username yet' }}
                                </div>
                                <div class="pill">{{ $mentor['active_assignment_count'] }} founders</div>
                            </div>
                            <div class="grid-2" style="margin-top: 12px;">
                                <input type="text" name="full_name" value="{{ $mentor['full_name'] }}" placeholder="Mentor name" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <input type="email" name="email" value="{{ $mentor['email'] }}" placeholder="Email" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <input type="text" name="phone" value="{{ $mentor['phone'] }}" placeholder="Phone" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <input type="text" name="country" value="{{ $mentor['country'] }}" placeholder="Country code" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <input type="text" name="timezone" value="{{ $mentor['timezone'] }}" placeholder="Timezone" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <select name="status" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                    @foreach (['active', 'paused', 'blocked'] as $status)
                                        <option value="{{ $status }}" @selected($mentor['status'] === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid-2" style="margin-top: 12px;">
                                @foreach ($mentorPermissionOptions as $permission)
                                    <label class="pill" style="display:flex;align-items:center;gap:8px;justify-content:flex-start;">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, $mentor['permissions'], true))>
                                        <span>{{ str_replace('_', ' ', ucfirst($permission)) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="cta-row">
                                <button class="btn primary" type="submit">Save Mentor Access</button>
                            </div>
                        </form>
                    @empty
                        <div class="stack-item">
                            <strong>No mentors synced yet</strong><br>
                            Once mentor identities are in the OS, their profile and permission controls will appear here.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="card" style="margin-top: 22px;">
                <h2>Admin Profiles And Permissions</h2>
                <p class="muted">Admin access is where we keep the strongest guardrails. An empty admin permission set still behaves as full access for bootstrap admins, but explicit permissions are recommended.</p>
                <div class="stack" style="margin-top: 14px;">
                    @forelse ($admins as $adminUser)
                        <form method="POST" action="{{ route('admin.control.admin-profile') }}" class="stack-item">
                            @csrf
                            <input type="hidden" name="admin_id" value="{{ $adminUser['id'] }}">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:start;flex-wrap:wrap;">
                                <div>
                                    <strong>{{ $adminUser['full_name'] }}</strong><br>
                                    {{ $adminUser['email'] }} · {{ $adminUser['username'] ?: 'No username yet' }}
                                </div>
                                <div class="pill">{{ ucfirst($adminUser['status']) }}</div>
                            </div>
                            <div class="grid-2" style="margin-top: 12px;">
                                <input type="text" name="full_name" value="{{ $adminUser['full_name'] }}" placeholder="Admin name" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <input type="email" name="email" value="{{ $adminUser['email'] }}" placeholder="Email" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <input type="text" name="phone" value="{{ $adminUser['phone'] }}" placeholder="Phone" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <input type="text" name="country" value="{{ $adminUser['country'] }}" placeholder="Country code" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <input type="text" name="timezone" value="{{ $adminUser['timezone'] }}" placeholder="Timezone" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <select name="status" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                    @foreach (['active', 'paused', 'blocked'] as $status)
                                        <option value="{{ $status }}" @selected($adminUser['status'] === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid-2" style="margin-top: 12px;">
                                @foreach ($adminPermissionOptions as $permission)
                                    <label class="pill" style="display:flex;align-items:center;gap:8px;justify-content:flex-start;">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, $adminUser['permissions'], true))>
                                        <span>{{ str_replace('_', ' ', ucfirst($permission)) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="cta-row">
                                <button class="btn primary" type="submit">Save Admin Access</button>
                            </div>
                        </form>
                    @empty
                        <div class="stack-item">
                            <strong>No admin profiles yet</strong><br>
                            Once more admin identities are synced into the OS, they will be managed here.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
