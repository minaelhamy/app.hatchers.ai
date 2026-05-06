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
        $workspace = $dashboard['workspace'];
        $groups = $workspace['notification_groups'] ?? ['new' => [], 'earlier' => []];
        $actions = $workspace['next_best_actions'] ?? [];
        $founder = $dashboard['founder'];
    @endphp

    <div class="workspace-shell">
        <aside class="workspace-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $founder->company->business_model ?? 'hybrid',
                'activeKey' => 'inbox',
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
                @include('os.partials.guidebook-workspace-topbar', [
                    'founder' => $founder,
                    'company' => $founder->company,
                    'workspace' => $workspace,
                    'projectName' => $founder->company->company_name ?? 'Founder workspace',
                    'sectionLabel' => 'Inbox',
                    'searchPlaceholder' => 'Scan founder alerts, launch updates, and the next best actions...',
                ])
                <section class="hero">
                    <div class="eyebrow">Inbox</div>
                    <h1>Your OS inbox.</h1>
                    <p class="muted">One place for founder alerts, weekly execution signals, and the next actions the OS thinks matter most.</p>
                </section>

                <section class="card" style="margin-bottom:22px;">
                    <h2>How to use this</h2>
                    <p class="muted" style="margin-top:8px;">Use Inbox when you want to scan what changed across the OS quickly, then jump back into the actual workspace that needs action.</p>
                </section>

                <section class="grid-2">
                    <div class="card">
                        <h2>New</h2>
                        <div class="stack" style="margin-top:14px;">
                            @forelse ($groups['new'] as $item)
                                <div class="stack-item">
                                    <strong>{{ $item['title'] }}</strong><br>
                                    <span class="muted">{{ $item['meta'] }} · {{ $item['age_label'] }}</span>
                                </div>
                            @empty
                                <div class="stack-item">
                                    <strong>No new inbox items</strong><br>
                                    <span class="muted">You are currently caught up across the OS.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="card">
                        <h2>Next Best Actions</h2>
                        <div class="stack" style="margin-top:14px;">
                            @forelse ($actions as $action)
                                <a href="{{ $action['href'] }}" class="stack-item" style="text-decoration:none;color:inherit;">
                                    <strong>{{ $action['title'] }}</strong><br>
                                    <span class="muted">{{ $action['description'] }}</span>
                                    <div style="margin-top:10px;"><span class="pill">{{ $action['label'] }}</span></div>
                                </a>
                            @empty
                                <div class="stack-item">
                                    <strong>No action suggestions right now</strong><br>
                                    <span class="muted">The OS will surface guidance here as your tasks, campaigns, and business signals change.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="card" style="margin-top:22px;">
                    <h2>Earlier</h2>
                    <div class="stack" style="margin-top:14px;">
                        @forelse ($groups['earlier'] as $item)
                            <div class="stack-item">
                                <strong>{{ $item['title'] }}</strong><br>
                                <span class="muted">{{ $item['meta'] }} · {{ $item['age_label'] }}</span>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No earlier inbox items</strong><br>
                                <span class="muted">As more cross-tool activity lands in the OS, the inbox timeline will grow here.</span>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </main>

        <aside class="workspace-rightbar">
            <div class="workspace-rightbar-inner">
                <h3>Inbox Snapshot</h3>
                <div class="workspace-rail-list">
                    <div class="workspace-rail-item"><strong>New items</strong><br><span class="muted">{{ count($groups['new']) }}</span></div>
                    <div class="workspace-rail-item"><strong>Earlier items</strong><br><span class="muted">{{ count($groups['earlier']) }}</span></div>
                    <div class="workspace-rail-item"><strong>Purpose</strong><br><span class="muted">Scan first, then jump back into the real workspace that needs action.</span></div>
                </div>
            </div>
        </aside>
    </div>
@endsection
