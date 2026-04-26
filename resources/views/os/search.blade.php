@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .workspace-shell { min-height:100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 240px; background:#f8f5ee; }
        .workspace-sidebar, .workspace-rightbar { background:rgba(255,252,247,0.82); border-color:var(--line); border-style:solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .workspace-rightbar { border-width:0 0 0 1px; background:rgba(255,251,246,0.92); }
        .workspace-sidebar-inner, .workspace-rightbar-inner { padding:22px 18px; }
        .workspace-brand { display:inline-block; margin-bottom:24px; }
        .workspace-brand img { width:168px; height:auto; display:block; }
        .workspace-nav { display:grid; gap:6px; }
        .workspace-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .workspace-nav-item.active { background:#ece6db; }
        .workspace-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .workspace-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .workspace-user { display:flex; align-items:center; gap:10px; }
        .workspace-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .workspace-main { padding:26px 28px 28px; }
        .workspace-main-inner { max-width:900px; margin:0 auto; }
        .workspace-rightbar h3 { font-size:0.83rem; letter-spacing:0.06em; text-transform:uppercase; color:var(--muted); margin-bottom:12px; }
        .workspace-rail-list { display:grid; gap:10px; }
        .workspace-rail-item { background:rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:14px; padding:12px 14px; }
        @media (max-width:1240px) { .workspace-shell { grid-template-columns:220px 1fr; } .workspace-rightbar { display:none; } }
        @media (max-width:900px) { .workspace-shell { grid-template-columns:1fr; } .workspace-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .workspace-sidebar-footer { display:none; } .workspace-main { padding:20px 16px 24px; } }
    </style>
@endsection

@section('content')
    @php
        $founder = auth()->user();
    @endphp

    <div class="workspace-shell">
        <aside class="workspace-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $founder?->company?->business_model ?? 'hybrid',
                'activeKey' => 'search',
                'navClass' => 'workspace-nav',
                'itemClass' => 'workspace-nav-item',
                'iconClass' => 'workspace-nav-icon',
                'innerClass' => 'workspace-sidebar-inner',
                'brandClass' => 'workspace-brand',
                'footerClass' => 'workspace-sidebar-footer',
                'userClass' => 'workspace-user',
                'avatarClass' => 'workspace-avatar',
            ])
        </aside>

        <main class="workspace-main">
            <div class="workspace-main-inner">
                <section class="hero">
                    <div class="eyebrow">Unified Search</div>
                    <h1>Search across your OS work.</h1>
                    <p class="muted">Find tasks, lessons, campaigns, offers, and activity without jumping between tools.</p>
                    <form method="GET" action="{{ route('founder.search') }}" class="cta-row" style="margin-top:14px;">
                        <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Search your founder workspace" style="flex:1;min-width:240px;padding:12px 14px;border-radius:14px;border:1px solid var(--line);background:#fff;">
                        <button class="btn primary" type="submit">Search</button>
                    </form>
                </section>

                <section class="card" style="margin-bottom:22px;">
                    <h2>How to use this</h2>
                    <p class="muted" style="margin-top:8px;">Search is for finding where something lives fast. Use it when you already know roughly what you need and want the shortest path back into action.</p>
                </section>

                <section class="card">
                    <h2>Results</h2>
                    <div class="stack" style="margin-top:14px;">
                        @forelse ($results as $result)
                            <a href="{{ $result['href'] }}" class="stack-item" style="text-decoration:none;color:inherit;">
                                <div class="pill">{{ $result['type'] }}</div>
                                <strong style="display:block;margin-top:10px;">{{ $result['title'] }}</strong>
                                <div class="muted" style="margin-top:6px;">{{ $result['description'] }}</div>
                            </a>
                        @empty
                            <div class="stack-item">
                                <strong>{{ $searchQuery !== '' ? 'No matches yet' : 'Start with a search' }}</strong><br>
                                <span class="muted">{{ $searchQuery !== '' ? 'Try a task title, campaign name, offer, or a phrase from your recent activity.' : 'Search is unified across the founder OS state that already lives inside Hatchers Ai Business OS.' }}</span>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </main>

        <aside class="workspace-rightbar">
            <div class="workspace-rightbar-inner">
                <h3>Search Tips</h3>
                <div class="workspace-rail-list">
                    <div class="workspace-rail-item"><strong>Try offer names</strong><br><span class="muted">Useful when jumping back into Commerce fast.</span></div>
                    <div class="workspace-rail-item"><strong>Try lead or customer names</strong><br><span class="muted">Useful when moving between First 100, orders, and bookings.</span></div>
                    <div class="workspace-rail-item"><strong>Try campaign titles</strong><br><span class="muted">Useful when searching across Marketing and website work.</span></div>
                </div>
            </div>
        </aside>
    </div>
@endsection
