@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'prototype-dashboard-page')

@php
    $founder = $dashboard['founder'];
    $workspace = $dashboard['workspace'];
    $notificationGroups = $workspace['notification_groups'] ?? ['new' => [], 'earlier' => []];
    $unreadNotificationCount = $workspace['unread_notification_count'] ?? 0;
@endphp

@section('head')
    <style>
        .page.prototype-dashboard-page {
            --bg: #F9F8F6; --surface: #FBFAF7; --surface-2: #F4F1EC; --border: rgba(30, 24, 16, 0.10); --hairline: rgba(30, 24, 16, 0.08); --text: #1B1A17; --text-muted: #6B6660; --text-subtle: #A39E96; --accent-pink: #F2546B; --tile-purple: #C8B8D6; --tile-purple-2: #A99BBC; --tile-grey: #B8B0A6; --tile-grey-2: #8E867C; --shadow-sm: 0 1px 0 rgba(30,24,16,0.04); --shadow-md: 0 1px 2px rgba(30,24,16,0.06), 0 0 0 0.5px rgba(30,24,16,0.06);
            min-height: 100vh; padding: 0; background: var(--bg); font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: var(--text);
        }
        .page.prototype-dashboard-page * { box-sizing: border-box; }
        .prototype-app { background: var(--bg); display: grid; grid-template-columns: auto 1fr; min-height: 100vh; }
        .rail { width:56px; border-right:0.5px solid var(--hairline); padding:14px 0; display:flex; flex-direction:column; align-items:center; justify-content:space-between; background:var(--bg); }
        .rail-top,.rail-bottom { display:flex; flex-direction:column; align-items:center; gap:16px; }
        .rail-icon { width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center; color:#6B6660; border-radius:6px; text-decoration:none; background:transparent; font-size:16px; }
        .rail-icon:hover { color:var(--text); background:var(--surface-2); }
        .rail-add { background:#ECE6FA; color:#5B45C9; border:0.5px solid #C9BCF0; position:relative; }
        .rail-tooltip { position:absolute; left:calc(100% + 10px); top:50%; transform:translateY(-50%); background:#fff; border:0.5px solid var(--border); border-radius:8px; padding:5px 10px; font-size:12px; color:var(--text); white-space:nowrap; box-shadow:var(--shadow-md); opacity:0; pointer-events:none; }
        .rail-add:hover .rail-tooltip { opacity:1; }
        .rail-avatar { width:28px; height:28px; border-radius:8px; background:linear-gradient(160deg, #7C5BE0, #5B3FC9); color:#fff; font-size:12px; font-weight:600; display:inline-flex; align-items:center; justify-content:center; }
        .main { display:flex; flex-direction:column; min-width:0; }
        .topbar { display:grid; grid-template-columns:auto 1fr auto; align-items:center; gap:16px; padding:14px 20px; border-bottom:0.5px solid var(--hairline); background:var(--bg); }
        .brand { display:inline-flex; align-items:center; gap:10px; padding:6px 12px 6px 8px; background:var(--surface); border:0.5px solid var(--border); border-radius:999px; box-shadow:var(--shadow-sm); font-weight:600; font-size:13px; color:var(--text); text-decoration:none; }
        .brand-mark { width:18px; height:18px; border-radius:5px; background:var(--accent-pink); }
        .search { display:flex; align-items:center; gap:10px; height:36px; padding:0 14px; background:var(--surface); border:0.5px solid var(--border); border-radius:999px; box-shadow:var(--shadow-sm); max-width:560px; width:100%; justify-self:start; margin-left:4px; }
        .search-dot { width:6px; height:6px; border-radius:50%; background:#1B1A17; }
        .search input { flex:1; border:0; outline:0; background:transparent; font:inherit; color:var(--text); font-size:13px; }
        .search input::placeholder { color:var(--text-subtle); }
        .search-kbd { font-size:11px; color:var(--text-subtle); border:0.5px solid var(--border); border-radius:6px; padding:2px 7px; line-height:1; }
        .status-pill { display:inline-flex; align-items:center; gap:10px; padding:6px 14px 6px 10px; background:var(--surface); border:0.5px solid var(--border); border-radius:999px; box-shadow:var(--shadow-sm); font-size:12.5px; color:var(--text); text-decoration:none; }
        .bell-wrap { position:relative; width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center; }
        .bell-badge { position:absolute; top:-2px; right:-2px; min-width:14px; height:14px; padding:0 3px; border-radius:999px; background:var(--accent-pink); color:#fff; font-size:9px; font-weight:600; display:inline-flex; align-items:center; justify-content:center; line-height:1; border:1.5px solid var(--surface); }
        .content { flex:1; display:grid; grid-template-columns:140px 1fr; min-height:0; }
        .tile-rail { padding:24px 16px; display:flex; flex-direction:column; gap:24px; align-items:center; }
        .tile { width:92px; display:flex; flex-direction:column; align-items:center; gap:8px; text-decoration:none; color:inherit; }
        .tile-art { width:88px; height:88px; border-radius:18px; display:flex; align-items:center; justify-content:center; color:#fff; box-shadow:inset 0 1px 0 rgba(255,255,255,0.35), inset 0 -10px 24px rgba(0,0,0,0.12), 0 1px 2px rgba(30,24,16,0.08); position:relative; overflow:hidden; font-size:28px; }
        .tile-art::after { content:""; position:absolute; inset:0; background:linear-gradient(160deg, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0) 45%, rgba(0,0,0,0.10) 100%); }
        .tile-art.purple { background:linear-gradient(160deg, var(--tile-purple) 0%, var(--tile-purple-2) 100%); }
        .tile-art.grey { background:linear-gradient(160deg, var(--tile-grey) 0%, var(--tile-grey-2) 100%); }
        .tile-label { font-size:12px; color:var(--text); font-weight:500; text-align:center; }
        .workspace { padding:28px 40px 60px; }
        .feed-window { width:min(980px, calc(100% - 40px)); margin:0 auto; background:var(--surface); border:0.5px solid var(--border); border-radius:18px; box-shadow:var(--shadow-md); overflow:hidden; }
        .feed-window-header { display:flex; align-items:center; justify-content:center; position:relative; padding:14px 20px; border-bottom:0.5px solid var(--hairline); }
        .traffic { position:absolute; left:18px; display:inline-flex; gap:7px; align-items:center; }
        .traffic span { width:12px; height:12px; border-radius:50%; display:inline-block; box-shadow: inset 0 0 0 0.5px rgba(0,0,0,0.10); }
        .traffic .red { background:#ED6A5E; } .traffic .yellow { background:#F4BF4F; } .traffic .green { background:#62C554; }
        .feed-window-title { font-size:11px; font-weight:600; letter-spacing:0.10em; text-transform:uppercase; color:var(--text-muted); }
        .feed-window-body { padding:24px; }
        .feed-filter { display:flex; gap:10px; margin-bottom:18px; }
        .feed-pill { padding:10px 14px; border-radius:999px; background:#fff; border:0.5px solid var(--border); color:var(--text-muted); font-size:13px; font-weight:600; }
        .feed-pill.active { background:var(--surface-2); color:var(--text); }
        .section-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.08em; color:var(--text-muted); margin:18px 0 10px; }
        .feed-list { display:grid; gap:12px; }
        .feed-item { display:flex; gap:14px; align-items:flex-start; background:#fff; border:0.5px solid var(--border); border-radius:16px; padding:14px 16px; box-shadow:var(--shadow-sm); }
        .feed-icon { width:40px; height:40px; border-radius:999px; display:grid; place-items:center; color:#fff; font-weight:700; flex-shrink:0; background:linear-gradient(135deg, #8e1c74, #ff2c35); }
        .feed-title { font-size:16px; font-weight:600; line-height:1.35; }
        .feed-time { color:var(--text-muted); margin-top:4px; font-size:13px; }
        .empty-state { display:flex; flex-direction:column; align-items:center; justify-content:flex-start; text-align:center; padding:24px 0 6px; gap:4px; }
        .empty-state h2 { margin:0; font-size:14px; font-weight:600; color:var(--text); }
        .empty-state p { margin:0; font-size:13px; color:var(--text-subtle); }
        @media (max-width: 980px) { .content { grid-template-columns:1fr; } .tile-rail { flex-direction:row; justify-content:center; padding:20px; } .workspace { padding:20px; } }
    </style>
@endsection

@section('content')
    <div class="prototype-app">
        <aside class="rail">
            <div class="rail-top">
                <a href="{{ route('dashboard') }}" class="rail-icon" aria-label="Dashboard">▥</a>
                <a href="{{ route('founder.settings') }}" class="rail-icon" aria-label="Settings">⚙</a>
                <a href="{{ route('founder.ai-tools') }}" class="rail-icon rail-add" aria-label="New Agent">＋<span class="rail-tooltip">New Agent</span></a>
            </div>
            <div class="rail-bottom">
                <a href="{{ route('founder.inbox') }}" class="rail-icon" aria-label="Inbox">✉</a>
                <span class="rail-avatar">{{ strtoupper(substr((string) ($founder->full_name ?? 'J'), 0, 1)) }}</span>
            </div>
        </aside>
        <div class="main">
            <div class="topbar">
                <a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark"></span><span>Hatchers AI OS</span></a>
                <div class="search"><span class="search-dot"></span><input type="text" placeholder="What would you like to do?"><span class="search-kbd">⌘K</span></div>
                <a href="{{ route('founder.notifications') }}" class="status-pill"><span class="bell-wrap">🔔 @if($unreadNotificationCount)<span class="bell-badge">{{ $unreadNotificationCount }}</span>@endif</span><span>{{ now()->format('D, M j g:i A') }}</span></a>
            </div>
            <div class="content">
                <div class="tile-rail">
                    <a class="tile" href="{{ route('founder.tasks') }}"><div class="tile-art purple">☷</div><div class="tile-label">Tasks</div></a>
                    <a class="tile" href="{{ route('founder.inbox') }}"><div class="tile-art grey">⌂</div><div class="tile-label">Inbox</div></a>
                    <a class="tile" href="{{ route('founder.ai-tools') }}"><div class="tile-art grey">✦</div><div class="tile-label">AI Tools</div></a>
                </div>
                <div class="workspace">
                    <div class="feed-window">
                        <div class="feed-window-header">
                            <span class="traffic"><span class="red"></span><span class="yellow"></span><span class="green"></span></span>
                            <span class="feed-window-title">NOTIFICATIONS</span>
                        </div>
                        <div class="feed-window-body">
                            <div class="feed-filter">
                                <span class="feed-pill active">All</span>
                                <span class="feed-pill">Unread</span>
                            </div>
                            <div class="section-label">New</div>
                            <div class="feed-list">
                                @forelse($notificationGroups['new'] as $notification)
                                    <div class="feed-item">
                                        <div class="feed-icon">{{ strtoupper(substr((string) ($notification['kind'] ?? 'n'), 0, 1)) }}</div>
                                        <div>
                                            <div class="feed-title">{{ $notification['title'] }}</div>
                                            <div class="feed-time">{{ $notification['age_label'] }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty-state"><h2>No new notifications right now.</h2><p>You’re up to date.</p></div>
                                @endforelse
                            </div>
                            <div class="section-label">Earlier</div>
                            <div class="feed-list">
                                @forelse($notificationGroups['earlier'] as $notification)
                                    <div class="feed-item">
                                        <div class="feed-icon">{{ strtoupper(substr((string) ($notification['kind'] ?? 'n'), 0, 1)) }}</div>
                                        <div>
                                            <div class="feed-title">{{ $notification['title'] }}</div>
                                            <div class="feed-time">{{ $notification['age_label'] }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty-state"><h2>No earlier notifications yet.</h2><p>As more OS activity lands, it will appear here.</p></div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
