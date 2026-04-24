@extends('os.layout')

@section('content')
    @php
        $admin = $workspace['admin'];
        $metrics = $workspace['metrics'];
        $groups = $workspace['groups'];
        $rules = $workspace['login_authority_rules'];
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
                <a class="nav-item active" href="{{ route('admin.identity') }}">Identity</a>
                <a class="nav-item" href="{{ route('admin.commerce') }}">Commerce Control</a>
                <a class="nav-item" href="{{ route('admin.modules') }}">Module Monitoring</a>
                <a class="nav-item" href="{{ route('admin.support') }}">Support Center</a>
                <a class="nav-item" href="/dashboard">OS Home</a>
            </div>
        </aside>

        <div>
            <section class="hero">
                <div class="eyebrow">Identity Workspace</div>
                <h1>Track identity health and OS-first login authority.</h1>
                <p class="muted">Welcome back, {{ $admin->full_name }}. This workspace makes it easier to see where OS accounts came from, how fresh they are, and what the OS should trust during authentication.</p>
                <div class="cta-row">
                    <form method="POST" action="{{ route('admin.identity.backfill') }}">
                        @csrf
                        <button class="btn primary" type="submit">Backfill Identity Metadata</button>
                    </form>
                </div>
            </section>

            @if (session('success'))
                <section class="card" style="border-color: rgba(44, 122, 87, 0.25); background: rgba(44, 122, 87, 0.06); margin-bottom: 18px;">
                    <h3 style="color: var(--success);">Action completed</h3>
                    <p class="muted" style="margin-top: 8px;">{{ session('success') }}</p>
                </section>
            @endif

            <section class="metrics" style="margin-bottom: 22px;">
                <div class="card metric"><div class="muted">Total users</div><strong>{{ $metrics['total_users'] }}</strong></div>
                <div class="card metric"><div class="muted">Founders</div><strong>{{ $metrics['founders'] }}</strong></div>
                <div class="card metric"><div class="muted">Mentors</div><strong>{{ $metrics['mentors'] }}</strong></div>
                <div class="card metric"><div class="muted">Admins</div><strong>{{ $metrics['admins'] }}</strong></div>
                <div class="card metric"><div class="muted">OS native</div><strong>{{ $metrics['os_native'] }}</strong></div>
                <div class="card metric"><div class="muted">LMS bridge</div><strong>{{ $metrics['lms_bridge'] }}</strong></div>
                <div class="card metric"><div class="muted">Integration sync</div><strong>{{ $metrics['integration_sync'] }}</strong></div>
                <div class="card metric"><div class="muted">Stale identities</div><strong>{{ $metrics['stale_identities'] }}</strong></div>
                <div class="card metric"><div class="muted">Unknown source</div><strong>{{ $metrics['unknown_identity_source'] }}</strong></div>
            </section>

            <section class="grid-2" style="margin-top:22px;">
                <div class="card">
                    <h2>Login Authority Rules</h2>
                    <div class="stack" style="margin-top:14px;">
                        @foreach ($rules as $rule)
                            <div class="stack-item">{{ $rule }}</div>
                        @endforeach
                    </div>
                </div>

                <div class="card">
                    <h2>Identity Notes</h2>
                    <div class="stack" style="margin-top:14px;">
                        <div class="stack-item"><strong>OS native</strong><br>Account was created directly in Hatchers Ai OS, such as founder signup.</div>
                        <div class="stack-item"><strong>LMS bridge</strong><br>Account was refreshed or first created by a successful LMS fallback login.</div>
                        <div class="stack-item"><strong>Integration sync</strong><br>Account was upserted into the OS by a signed backend identity sync request.</div>
                    </div>
                </div>
            </section>

            @foreach (['founders' => 'Founders', 'mentors' => 'Mentors', 'admins' => 'Admins'] as $key => $label)
                <section class="card" style="margin-top:22px;">
                    <h2>{{ $label }}</h2>
                    <div class="stack" style="margin-top:14px;">
                        @forelse ($groups[$key] as $user)
                            <div class="stack-item">
                                <div style="display:flex;justify-content:space-between;gap:12px;align-items:start;flex-wrap:wrap;">
                                    <div>
                                        <strong>{{ $user['full_name'] }}</strong><br>
                                        {{ $user['email'] }} · {{ $user['username'] }}
                                        @if ($user['company_name'])
                                            · {{ $user['company_name'] }}
                                        @endif
                                    </div>
                                    <div class="pill" style="@if($user['sync_state']['tone'] === 'success') background: rgba(44,122,87,0.1); color: var(--success); border-color: rgba(44,122,87,0.18); @else background: rgba(154,107,27,0.08); color: var(--warning); border-color: rgba(154,107,27,0.18); @endif">
                                        {{ $user['sync_state']['label'] }}
                                    </div>
                                </div>
                                <div class="muted" style="margin-top:6px;">
                                    {{ ucfirst($user['status']) }} · {{ $user['auth_source_label'] }}
                                    @if ($user['last_synced_at'])
                                        · Last synced {{ $user['last_synced_at'] }}
                                    @endif
                                </div>
                                <div class="muted" style="margin-top:6px;">Identity key: {{ $user['identity_key'] }}</div>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No {{ strtolower($label) }} yet</strong><br>
                                As identities are created or synced into Hatchers Ai OS, they will appear here.
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>
@endsection
