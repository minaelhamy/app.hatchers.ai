@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .ops-shell { min-height: 100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 220px; background:#f8f5ee; }
        .ops-sidebar, .ops-rightbar { background: rgba(255,252,247,0.8); border-color: var(--line); border-style: solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .ops-rightbar { border-width:0 0 0 1px; background: rgba(255,251,246,0.9); }
        .ops-sidebar-inner, .ops-rightbar-inner { padding:22px 18px; }
        .ops-brand { display:inline-block; margin-bottom:24px; }
        .ops-brand img { width:168px; height:auto; display:block; }
        .ops-nav { display:grid; gap:6px; }
        .ops-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .ops-nav-item.active { background:#ece6db; }
        .ops-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .ops-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .ops-user { display:flex; align-items:center; gap:10px; }
        .ops-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .ops-main { padding:26px 28px 24px; }
        .ops-main-inner { max-width:780px; margin:0 auto; }
        .ops-main h1 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing:-0.02em; margin-bottom:6px; }
        .ops-main p { color:var(--muted); margin-bottom:24px; }
        .ops-metrics { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:22px; }
        .ops-metric, .ops-card, .rail-item, .mini-note { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:16px 18px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .ops-metric strong { display:block; font-size:1.55rem; margin-top:6px; }
        .ops-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; }
        .ops-stack { display:grid; gap:10px; }
        @media (max-width:1240px) { .ops-shell { grid-template-columns:220px 1fr; } .ops-rightbar { display:none; } }
        @media (max-width:900px) { .ops-shell { grid-template-columns:1fr; } .ops-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .ops-sidebar-footer { display:none; } .ops-main { padding:20px 16px 24px; } .ops-grid, .ops-metrics { grid-template-columns:1fr; } }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $ops = $orderWorkspace;
    @endphp
    <div class="ops-shell">
        <aside class="ops-sidebar">
            <div class="ops-sidebar-inner">
                <a class="ops-brand" href="/dashboard/founder"><img src="/brand/hatchers-ai-logo.png" alt="Hatchers AI"></a>
                <nav class="ops-nav">
                    <a class="ops-nav-item" href="/dashboard/founder"><span class="ops-nav-icon">⌂</span><span>Home</span></a>
                    <a class="ops-nav-item active" href="{{ route('founder.commerce') }}"><span class="ops-nav-icon">⌁</span><span>Commerce</span></a>
                    <a class="ops-nav-item" href="{{ route('founder.ai-tools') }}"><span class="ops-nav-icon">✦</span><span>AI Tools</span></a>
                    <a class="ops-nav-item" href="{{ route('founder.learning-plan') }}"><span class="ops-nav-icon">▣</span><span>Learning Plan</span></a>
                    <a class="ops-nav-item" href="{{ route('founder.tasks') }}"><span class="ops-nav-icon">◌</span><span>Tasks</span></a>
                    <a class="ops-nav-item" href="{{ route('founder.settings') }}"><span class="ops-nav-icon">⚙</span><span>Settings</span></a>
                </nav>
            </div>
            <div class="ops-sidebar-footer">
                <div class="ops-user">
                    <div class="ops-avatar">{{ strtoupper(substr($founder->full_name, 0, 1)) }}</div>
                    <div>{{ $founder->full_name }}</div>
                </div>
                <form method="POST" action="/logout" style="margin:0;">@csrf<button class="ops-nav-icon" type="submit" style="border:0;background:transparent;cursor:pointer;">↘</button></form>
            </div>
        </aside>

        <main class="ops-main">
            <div class="ops-main-inner">
                <h1>Orders</h1>
                <p>Track Bazaar-driven order operations from Hatchers Ai Business OS while the storefront engine keeps doing the backend work.</p>

                <section class="ops-metrics">
                    <div class="ops-metric"><div class="muted">Orders</div><strong>{{ $ops['counts']['orders'] }}</strong></div>
                    <div class="ops-metric"><div class="muted">Products</div><strong>{{ $ops['counts']['products'] }}</strong></div>
                    <div class="ops-metric"><div class="muted">Customers</div><strong>{{ $ops['counts']['customers'] }}</strong></div>
                    <div class="ops-metric"><div class="muted">Revenue</div><strong>{{ $ops['currency'] }} {{ number_format($ops['gross_revenue'], 0) }}</strong></div>
                </section>

                <section class="ops-grid">
                    <div class="ops-card">
                        <h2 style="margin-bottom:12px;">Store Overview</h2>
                        <div class="ops-stack">
                            <div><strong>{{ $ops['website_title'] }}</strong></div>
                            <div class="muted">Readiness {{ $ops['readiness_score'] }}% · Last synced {{ $ops['updated_at'] ?: 'Not synced yet' }}</div>
                            <div class="muted">This page is the OS-native operations view for Bazaar order data.</div>
                            <div style="margin-top:8px;">
                                <a class="pill" href="{{ route('founder.commerce') }}">Back to Commerce</a>
                            </div>
                        </div>
                    </div>

                    <div class="ops-card">
                        <h2 style="margin-bottom:12px;">Order Activity</h2>
                        <div class="ops-stack">
                            @forelse ($ops['activity'] as $item)
                                <div class="rail-item">{{ $item }}</div>
                            @empty
                                <div class="rail-item">No Bazaar order activity has synced yet.</div>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <aside class="ops-rightbar">
            <div class="ops-rightbar-inner">
                <h3 style="font-size:0.83rem;letter-spacing:0.06em;text-transform:uppercase;color:var(--muted);margin-bottom:12px;">Connected Tools</h3>
                <div class="ops-stack">
                    @foreach ($launchCards as $launch)
                        <div class="rail-item">
                            <strong>{{ $launch['label'] }}</strong><br>
                            <span class="muted">{{ $launch['description'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mini-note" style="margin-top:18px;">Orders now have a dedicated OS view. The next deeper step is item-level order details and status actions without leaving Hatchers Ai Business OS.</div>
            </div>
        </aside>
    </div>
@endsection
