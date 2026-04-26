@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .analytics-shell { min-height: 100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 220px; background:#f8f5ee; }
        .analytics-sidebar, .analytics-rightbar { background: rgba(255,252,247,0.8); border-color: var(--line); border-style: solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .analytics-rightbar { border-width:0 0 0 1px; background: rgba(255,251,246,0.9); }
        .analytics-sidebar-inner, .analytics-rightbar-inner { padding:22px 18px; }
        .analytics-brand { display:inline-block; margin-bottom:24px; }
        .analytics-brand img { width:168px; height:auto; display:block; }
        .analytics-nav { display:grid; gap:6px; }
        .analytics-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .analytics-nav-item.active { background:#ece6db; }
        .analytics-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .analytics-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .analytics-user { display:flex; align-items:center; gap:10px; }
        .analytics-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .analytics-main { padding:26px 28px 24px; }
        .analytics-main-inner { max-width:780px; margin:0 auto; }
        .analytics-main h1 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing:-0.02em; margin-bottom:6px; }
        .analytics-main p { color:var(--muted); margin-bottom:24px; }
        .analytics-metrics { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:22px; }
        .analytics-metric, .analytics-card, .rail-item, .mini-note { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:16px 18px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .analytics-metric strong { display:block; font-size:1.55rem; margin-top:6px; }
        .analytics-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
        .analytics-rightbar h3 { font-size:0.83rem; letter-spacing:0.06em; text-transform:uppercase; color:var(--muted); margin-bottom:12px; }
        .rail-list { display:grid; gap:10px; margin-top:14px; }
        @media (max-width:1240px) { .analytics-shell { grid-template-columns:220px 1fr; } .analytics-rightbar { display:none; } }
        @media (max-width:900px) { .analytics-shell { grid-template-columns:1fr; } .analytics-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .analytics-sidebar-footer { display:none; } .analytics-main { padding:20px 16px 24px; } .analytics-grid, .analytics-metrics { grid-template-columns:1fr; } }
    </style>
@endsection

@section('content')
    @php $founder = $dashboard['founder']; @endphp
    <div class="analytics-shell">
        <aside class="analytics-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $founder->company->business_model ?? 'hybrid',
                'activeKey' => 'ai-tools',
                'navClass' => 'analytics-nav',
                'itemClass' => 'analytics-nav-item',
                'iconClass' => 'analytics-nav-icon',
                'innerClass' => 'analytics-sidebar-inner',
                'brandClass' => 'analytics-brand',
                'footerClass' => 'analytics-sidebar-footer',
                'userClass' => 'analytics-user',
                'avatarClass' => 'analytics-avatar',
            ])
        </aside>

        <main class="analytics-main">
            <div class="analytics-main-inner">
                <h1>Analytics</h1>
                <p>Unified performance across execution, growth, and marketing from one founder workspace.</p>

                <section class="analytics-metrics">
                    @foreach ($analytics['headline_metrics'] as $metric)
                        <div class="analytics-metric">
                            <div class="muted">{{ $metric['label'] }}</div>
                            <strong>{{ $metric['value'] }}</strong>
                        </div>
                    @endforeach
                </section>

                <section class="analytics-grid">
                    <div class="analytics-card">
                        <h2>Execution</h2>
                        <div class="stack" style="margin-top:14px;">
                            @foreach ($analytics['execution'] as $item)
                                <div class="stack-item"><strong>{{ $item['value'] }}</strong><br><span class="muted">{{ $item['label'] }}</span></div>
                            @endforeach
                        </div>
                    </div>
                    <div class="analytics-card">
                        <h2>Growth</h2>
                        <div class="stack" style="margin-top:14px;">
                            @foreach ($analytics['growth'] as $item)
                                <div class="stack-item"><strong>{{ $item['value'] }}</strong><br><span class="muted">{{ $item['label'] }}</span></div>
                            @endforeach
                        </div>
                    </div>
                    <div class="analytics-card">
                        <h2>Marketing</h2>
                        <div class="stack" style="margin-top:14px;">
                            @foreach ($analytics['marketing'] as $item)
                                <div class="stack-item"><strong>{{ $item['value'] }}</strong><br><span class="muted">{{ $item['label'] }}</span></div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <aside class="analytics-rightbar">
            <div class="analytics-rightbar-inner">
                <h3>OS Direction</h3>
                <div class="rail-list">
                    <div class="mini-note">Analytics should stay inside Hatchers Ai Business OS so founders can understand progress, growth, and marketing from one place.</div>
                    <div class="rail-item">
                        <div style="font-weight:600;">Next step</div>
                        <div style="margin-top:4px;color:var(--muted);">Use Commerce, Marketing, and Tasks directly from the OS instead of jumping between old tools.</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
@endsection
