@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .activity-shell { min-height: 100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 240px; background:#f8f5ee; }
        .activity-sidebar, .activity-rightbar { background: rgba(255,252,247,0.8); border-color: var(--line); border-style: solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .activity-rightbar { border-width:0 0 0 1px; background: rgba(255,251,246,0.9); }
        .activity-sidebar-inner, .activity-rightbar-inner { padding:22px 18px; }
        .activity-brand { display:inline-block; margin-bottom:24px; }
        .activity-brand img { width:168px; height:auto; display:block; }
        .activity-nav { display:grid; gap:6px; }
        .activity-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .activity-nav-item.active { background:#ece6db; }
        .activity-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .activity-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .activity-user { display:flex; align-items:center; gap:10px; min-width:0; }
        .activity-avatar, .activity-timeline-icon { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .activity-main { padding:26px 28px 24px; }
        .activity-main-inner { max-width:760px; margin:0 auto; }
        .activity-main h1 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing:-0.02em; margin-bottom:6px; }
        .activity-main p { color:var(--muted); margin-bottom:24px; }
        .activity-grid { display:grid; gap:18px; }
        .activity-card, .activity-stat, .activity-rail-card { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:18px 20px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .activity-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
        .activity-stat strong { display:block; font-size:1.55rem; margin-top:6px; }
        .activity-actions { display:grid; gap:12px; }
        .activity-action { display:flex; justify-content:space-between; gap:14px; align-items:flex-start; padding:14px 16px; border:1px solid rgba(220,207,191,0.72); border-radius:16px; background:#fffdf9; }
        .activity-action a { text-decoration:none; }
        .activity-pill { display:inline-flex; align-items:center; justify-content:center; padding:8px 12px; border-radius:999px; background:#ece6db; color:var(--ink); font-size:0.86rem; text-decoration:none; border:1px solid rgba(220,207,191,0.8); }
        .activity-section-title { font-size:1.05rem; margin-bottom:12px; }
        .activity-timeline { display:grid; gap:12px; }
        .activity-timeline-item { display:flex; gap:12px; align-items:flex-start; padding:14px 0; border-top:1px solid rgba(220,207,191,0.5); }
        .activity-timeline-item:first-child { border-top:0; padding-top:0; }
        .activity-time { color:var(--muted); font-size:0.92rem; margin-top:4px; }
        .activity-module-groups { display:grid; gap:12px; }
        .activity-module-group { border:1px solid rgba(220,207,191,0.72); border-radius:16px; padding:14px 16px; background:#fffdf9; }
        .sync-issue { border:1px solid rgba(179,34,83,0.14); background:rgba(179,34,83,0.05); border-radius:14px; padding:12px 14px; margin-top:10px; }
        .tone-success { color:#21643a; }
        .tone-warning { color:#9a6b1b; }
        .tone-danger { color:#b32253; }
        @media (max-width:1240px) { .activity-shell { grid-template-columns:220px 1fr; } .activity-rightbar { display:none; } }
        @media (max-width:900px) { .activity-shell { grid-template-columns:1fr; } .activity-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .activity-sidebar-footer { display:none; } .activity-main { padding:20px 16px 24px; } .activity-stats { grid-template-columns:1fr; } }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $workspace = $dashboard['workspace'];
        $activityFeed = $dashboard['activity_feed'];
        $syncStatus = $dashboard['sync_status'];
        $growth = $dashboard['growth'];
    @endphp

    <div class="activity-shell">
        <aside class="activity-sidebar">
            <div class="activity-sidebar-inner">
                <a class="activity-brand" href="/dashboard/founder"><img src="/brand/hatchers-ai-logo.png" alt="Hatchers AI"></a>
                <nav class="activity-nav">
                    <a class="activity-nav-item" href="/dashboard/founder"><span class="activity-nav-icon">⌂</span><span>Home</span></a>
                    <a class="activity-nav-item" href="{{ route('founder.first-100') }}"><span class="activity-nav-icon">◎</span><span>First 100</span></a>
                    <a class="activity-nav-item active" href="{{ route('founder.activity') }}"><span class="activity-nav-icon">◫</span><span>Activity</span></a>
                    <a class="activity-nav-item" href="{{ route('founder.commerce') }}"><span class="activity-nav-icon">⌁</span><span>Commerce</span></a>
                    <a class="activity-nav-item" href="{{ route('founder.ai-tools') }}"><span class="activity-nav-icon">✦</span><span>AI Tools</span></a>
                    <a class="activity-nav-item" href="{{ route('founder.learning-plan') }}"><span class="activity-nav-icon">▣</span><span>Learning Plan</span></a>
                    <a class="activity-nav-item" href="{{ route('founder.tasks') }}"><span class="activity-nav-icon">◌</span><span>Tasks</span></a>
                    <a class="activity-nav-item" href="{{ route('founder.settings') }}"><span class="activity-nav-icon">⚙</span><span>Settings</span></a>
                </nav>
            </div>
            <div class="activity-sidebar-footer">
                <div class="activity-user">
                    <div class="activity-avatar">{{ strtoupper(substr($founder->full_name, 0, 1)) }}</div>
                    <div>{{ $founder->full_name }}</div>
                </div>
                <form method="POST" action="/logout" style="margin:0;">@csrf<button class="activity-nav-icon" type="submit" style="border:0;background:transparent;cursor:pointer;">↘</button></form>
            </div>
        </aside>

        <main class="activity-main">
            <div class="activity-main-inner">
                <h1>Activity</h1>
                <p>Your cross-tool founder activity center inside Hatchers Ai Business OS.</p>

                <section class="activity-stats">
                    <div class="activity-stat">
                        <div class="muted">Tracked updates</div>
                        <strong>{{ count($activityFeed) }}</strong>
                    </div>
                    <div class="activity-stat">
                        <div class="muted">Healthy modules</div>
                        <strong>{{ $syncStatus['healthy_count'] }}</strong>
                    </div>
                    <div class="activity-stat">
                        <div class="muted">Revenue tracked</div>
                        <strong>{{ $growth['gross_revenue_formatted'] }}</strong>
                    </div>
                </section>

                <div class="activity-grid" style="margin-top:18px;">
                    <section class="activity-card">
                        <h2 class="activity-section-title">Next Best Actions</h2>
                        <div class="activity-actions">
                            @foreach ($workspace['next_best_actions'] as $action)
                                <div class="activity-action">
                                    <div>
                                        <strong>{{ $action['title'] }}</strong>
                                        <div class="muted" style="margin-top:4px;">{{ $action['description'] }}</div>
                                    </div>
                                    <a class="activity-pill" href="{{ $action['href'] }}">{{ $action['label'] }}</a>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="activity-card" id="sync-issues">
                        <h2 class="activity-section-title">Sync Trust</h2>
                        @forelse ($syncStatus['modules'] as $status)
                            <div class="sync-issue">
                                <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                                    <strong>{{ $status['module'] }}</strong>
                                    <span class="activity-pill {{ 'tone-' . $status['tone'] }}">{{ $status['status'] }}</span>
                                </div>
                                <div class="muted" style="margin-top:6px;">{{ $status['reason'] }}</div>
                                <div class="muted" style="margin-top:4px;">{{ $status['updated_at'] ?: 'Not synced yet' }}</div>
                            </div>
                        @empty
                            <div class="muted">No module trust data yet.</div>
                        @endforelse
                    </section>

                    <section class="activity-card">
                        <h2 class="activity-section-title">Unified Timeline</h2>
                        <div class="activity-timeline">
                            @forelse ($activityFeed as $item)
                                <div class="activity-timeline-item">
                                    <div class="activity-timeline-icon">{{ strtoupper(substr($item['module'], 0, 1)) }}</div>
                                    <div>
                                        <strong>{{ $item['module'] }}</strong>
                                        <div style="margin-top:4px;">{{ $item['message'] }}</div>
                                        <div class="activity-time">{{ $item['updated_at'] ?: 'Recently' }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="muted">No cross-tool activity has synced into the OS yet.</div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </div>
        </main>

        <aside class="activity-rightbar">
            <div class="activity-rightbar-inner">
                <div class="activity-rail-card">
                    <h3 style="margin:0 0 12px;font-size:0.83rem;letter-spacing:0.06em;text-transform:uppercase;color:var(--muted);">By Module</h3>
                    <div class="activity-module-groups">
                        @foreach ($workspace['activity_feed_groups'] as $group)
                            <div class="activity-module-group">
                                <strong>{{ $group['module'] }}</strong>
                                <div class="muted" style="margin-top:6px;">{{ count($group['items']) }} recent update(s)</div>
                                @foreach (array_slice($group['items'], 0, 2) as $item)
                                    <div class="muted" style="margin-top:8px;">{{ $item['message'] }}</div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </aside>
    </div>
@endsection
