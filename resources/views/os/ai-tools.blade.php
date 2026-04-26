@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .tools-shell { min-height: 100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 220px; background:#f8f5ee; }
        .tools-sidebar, .tools-rightbar { background: rgba(255,252,247,0.8); border-color: var(--line); border-style: solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .tools-rightbar { border-width:0 0 0 1px; background: rgba(255,251,246,0.9); }
        .tools-sidebar-inner, .tools-rightbar-inner { padding:22px 18px; }
        .tools-brand { display:inline-block; margin-bottom:24px; }
        .tools-brand img { width:168px; height:auto; display:block; }
        .tools-nav { display:grid; gap:6px; }
        .tools-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .tools-nav-item.active { background:#ece6db; }
        .tools-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .tools-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .tools-user { display:flex; align-items:center; gap:10px; }
        .tools-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .tools-main { padding:26px 28px 24px; }
        .tools-main-inner { max-width:760px; margin:0 auto; }
        .tools-main h1 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing:-0.02em; margin-bottom:6px; }
        .tools-main p { color:var(--muted); margin-bottom:24px; }
        .tools-section { margin-bottom:22px; }
        .tools-section h2 { font-size:1.08rem; margin-bottom:12px; }
        .tools-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; }
        .tool-card { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:18px 18px 16px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .tool-card-title { font-size:1rem; font-weight:700; margin-bottom:6px; }
        .tool-card-copy { color:var(--muted); font-size:0.95rem; line-height:1.45; }
        .tool-card-row { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
        .tool-card-icon { width:34px; height:34px; border-radius:10px; border:1px solid rgba(222,60,109,0.24); color:#e02961; display:grid; place-items:center; flex-shrink:0; }
        .tool-card-cta { display:inline-block; margin-top:14px; padding:10px 14px; border-radius:10px; text-decoration:none; background:linear-gradient(90deg,#8e1c74,#ff2c35); color:white; font-weight:600; }
        .tool-card-secondary { display:inline-block; margin-top:14px; padding:10px 14px; border-radius:10px; text-decoration:none; background:#f0ece4; color:#5d554a; font-weight:600; }
        .tools-highlight { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:18px 20px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .highlight-label { font-size:0.82rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--rose); margin-bottom:8px; }
        .highlight-copy { color:var(--muted); line-height:1.5; margin-top:4px; }
        .highlight-badge { display:inline-block; margin-top:12px; padding:8px 14px; border-radius:10px; background:#f0ece4; color:#7a7267; font-size:0.95rem; }
        .tools-rightbar h3 { font-size:0.83rem; letter-spacing:0.06em; text-transform:uppercase; color:var(--muted); margin-bottom:12px; }
        .mini-note, .rail-item { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:14px; padding:12px 14px; }
        .rail-list { display:grid; gap:10px; margin-top:14px; }
        @media (max-width:1240px) { .tools-shell { grid-template-columns:220px 1fr; } .tools-rightbar { display:none; } }
        @media (max-width:900px) { .tools-shell { grid-template-columns:1fr; } .tools-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .tools-sidebar-footer { display:none; } .tools-main { padding:20px 16px 24px; } .tools-grid { grid-template-columns:1fr; } }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $workspace = $dashboard['workspace'];
        $launchCards = $launchCards ?? [];
        $aiTools = $workspace['ai_tools'];
        $primaryGoal = $dashboard['atlas']['primary_growth_goal'] ?? '';
        $recentCampaigns = $dashboard['atlas']['recent_campaigns'] ?? [];
        $moduleCards = $dashboard['module_cards'] ?? [];
    @endphp

    <div class="tools-shell">
        <aside class="tools-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $founder->company->business_model ?? 'hybrid',
                'activeKey' => 'ai-tools',
                'navClass' => 'tools-nav',
                'itemClass' => 'tools-nav-item',
                'iconClass' => 'tools-nav-icon',
                'innerClass' => 'tools-sidebar-inner',
                'brandClass' => 'tools-brand',
                'footerClass' => 'tools-sidebar-footer',
                'userClass' => 'tools-user',
                'avatarClass' => 'tools-avatar',
            ])
        </aside>

        <main class="tools-main">
            <div class="tools-main-inner">
                <h1>AI Tools</h1>
                <p>One place to use Atlas-led workflows, OS-native tools, and backend engines while Hatchers Ai OS absorbs the old products.</p>

                <section class="tools-section">
                    <div class="tools-highlight">
                        <div class="highlight-label">Recommended next move</div>
                        <div style="font-size:1.2rem;font-weight:700;">{{ $primaryGoal !== '' ? $primaryGoal : 'Use AI to move your next founder sprint forward.' }}</div>
                        <div class="highlight-copy">This workspace is the transition layer: the founder should work here, while Atlas, LMS, Bazaar, and Servio keep powering the backend until we fully absorb their frontends.</div>
                        <div class="highlight-badge">Ask AI anything about your project...</div>
                    </div>
                </section>

                <section class="tools-section">
                    <h2>OS AI Workflows</h2>
                    <div class="tools-grid">
                        @foreach ($aiTools as $tool)
                            <div class="tool-card">
                                <div class="tool-card-row">
                                    <div>
                                        <div class="tool-card-title">{{ $tool['title'] }}</div>
                                        <div class="tool-card-copy">Bring this workflow into Hatchers Ai OS so founders can use it without switching products.</div>
                                    </div>
                                    <div class="tool-card-icon">□</div>
                                </div>
                                <a class="tool-card-secondary" href="/dashboard/founder">Open from Home</a>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="tools-section">
                    <h2>Platform Workspaces</h2>
                    <div class="tools-grid">
                        <div class="tool-card">
                            <div class="tool-card-title">Unified Search</div>
                            <div class="tool-card-copy">Search tasks, lessons, campaigns, offers, and activity from one OS surface.</div>
                            <a class="tool-card-cta" href="{{ route('founder.search') }}">Open Search</a>
                        </div>
                        <div class="tool-card">
                            <div class="tool-card-title">Analytics</div>
                            <div class="tool-card-copy">Review execution, growth, and marketing performance in one reporting workspace.</div>
                            <a class="tool-card-cta" href="{{ route('founder.analytics') }}">Open Analytics</a>
                        </div>
                        <div class="tool-card">
                            <div class="tool-card-title">Media Library</div>
                            <div class="tool-card-copy">Keep campaign drafts, content assets, and offer copy together inside the OS.</div>
                            <a class="tool-card-cta" href="{{ route('founder.media-library') }}">Open Media Library</a>
                        </div>
                        <div class="tool-card">
                            <div class="tool-card-title">Automations</div>
                            <div class="tool-card-copy">Define saved cross-tool rules so the OS can become the long-term workflow layer.</div>
                            <a class="tool-card-cta" href="{{ route('founder.automations') }}">Open Automations</a>
                        </div>
                    </div>
                </section>

                <section class="tools-section">
                    <h2>Atlas-Led Actions</h2>
                    <div class="tools-grid">
                        <div class="tool-card">
                            <div class="tool-card-title">Campaign generation</div>
                            <div class="tool-card-copy">Use the OS marketing workspace to manage campaigns and content while Atlas continues powering the backend.</div>
                            <a class="tool-card-cta" href="{{ route('founder.marketing') }}">Open Marketing Workspace</a>
                        </div>
                        <div class="tool-card">
                            <div class="tool-card-title">Founder guidance</div>
                            <div class="tool-card-copy">Use the founder dashboard assistant and AI prompt bar to ask for next steps, messaging, offers, and weekly execution support.</div>
                            <a class="tool-card-secondary" href="/dashboard/founder">Go to Founder Home</a>
                        </div>
                    </div>
                </section>

                <section class="tools-section">
                    <h2>Connected Engines</h2>
                    <div class="tools-grid">
                        @foreach ($launchCards as $launch)
                            <div class="tool-card">
                                <div class="tool-card-row">
                                    <div>
                                        <div class="tool-card-title">{{ $launch['label'] }}</div>
                                        <div class="tool-card-copy">{{ $launch['description'] }}</div>
                                    </div>
                                    <div class="tool-card-icon">{{ strtoupper(substr($launch['module'], 0, 1)) }}</div>
                                </div>
                                <a class="tool-card-cta" href="{{ route('founder.marketing') }}">Open in OS</a>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="tools-section">
                    <h2>Live AI and Module Context</h2>
                    <div class="tools-grid">
                        @foreach (array_slice($moduleCards, 0, 4) as $module)
                            <div class="tool-card">
                                <div class="tool-card-title">{{ $module['module'] }}</div>
                                <div class="tool-card-copy">{{ $module['description'] }}</div>
                                <div class="highlight-badge">{{ $module['readiness_score'] }}% readiness</div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </main>

        <aside class="tools-rightbar">
            <div class="tools-rightbar-inner">
                <h3>Recent Campaigns</h3>
                <div class="rail-list">
                    @forelse (array_slice($recentCampaigns, 0, 3) as $campaign)
                        <div class="rail-item">
                            <div style="font-weight:600;">{{ $campaign['title'] ?? 'Campaign' }}</div>
                            <div style="margin-top:4px;color:var(--muted);">{{ $campaign['description'] ?? 'Saved in Atlas.' }}</div>
                        </div>
                    @empty
                        <div class="rail-item">
                            <div style="font-weight:600;">No campaigns yet</div>
                            <div style="margin-top:4px;color:var(--muted);">Atlas campaigns will appear here as they sync into the OS.</div>
                        </div>
                    @endforelse
                </div>

                <h3 style="margin-top:22px;">OS Direction</h3>
                <div class="mini-note">This page is the beginning of the unified tools layer. Over time these tool cards should become fully OS-native workflows instead of launcher cards.</div>
            </div>
        </aside>
    </div>
@endsection
