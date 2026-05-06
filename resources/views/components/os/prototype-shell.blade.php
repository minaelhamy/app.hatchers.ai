@props([
    'founder' => null,
    'workspace' => [],
    'activeTile' => null,
    'searchPlaceholder' => 'What would you like to do?',
    'statusText' => null,
    'showAiToolsButton' => true,
    'showSidepane' => false,
    'recentItems' => [],
    'aiToolsMode' => 'link',
])

@php
    $founderName = trim((string) ($founder->full_name ?? 'Founder'));
    $founderInitial = strtoupper(substr($founderName !== '' ? $founderName : 'F', 0, 1));
    $unreadCount = (int) ($workspace['unread_notification_count'] ?? 0);
    $statusLabel = $statusText ?: now()->format('D, M j g:i A');
    $icons = [
        'panel-left' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 5.5A2.5 2.5 0 0 1 5.5 3h13A2.5 2.5 0 0 1 21 5.5v13a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 18.5z"/><path d="M9 3v18"/></svg>',
        'settings' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7Z"/><path d="M19.4 15a1 1 0 0 0 .2 1.1l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9V20a2 2 0 1 1-4 0v-.1a1 1 0 0 0-.6-.9 1 1 0 0 0-1.1.2l-.1.1a2 2 0 0 1-2.8-2.8l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6H4a2 2 0 1 1 0-4h.1a1 1 0 0 0 .9-.6 1 1 0 0 0-.2-1.1l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1 1 0 0 0 1.1.2 1 1 0 0 0 .6-.9V4a2 2 0 1 1 4 0v.1a1 1 0 0 0 .6.9 1 1 0 0 0 1.1-.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1 1 0 0 0-.2 1.1 1 1 0 0 0 .9.6H20a2 2 0 1 1 0 4h-.1a1 1 0 0 0-.9.6Z"/></svg>',
        'plus' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
        'inbox' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 12h-4l-2 3H8l-2-3H2"/><path d="M5.5 20.5h13a2 2 0 0 0 2-2v-11a2 2 0 0 0-2-2h-13a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2Z"/></svg>',
        'search' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="6"/></svg>',
        'list' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13"/><path d="M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg>',
        'sparkles' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9.5 4.5 11 8l3.5 1.5L11 11l-1.5 3.5L8 11l-3.5-1.5L8 8zM18 11l.8 2.2L21 14l-2.2.8L18 17l-.8-2.2L15 14l2.2-.8zM16 3l.5 1.5L18 5l-1.5.5L16 7l-.5-1.5L14 5l1.5-.5z"/></svg>',
        'bell' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10.3 21a1.7 1.7 0 0 0 3.4 0"/><path d="M4.8 17.5h14.4a1 1 0 0 0 .8-1.6l-1.7-2.3a1 1 0 0 1-.2-.6V10a6 6 0 1 0-12 0v3a1 1 0 0 1-.2.6l-1.7 2.3a1 1 0 0 0 .8 1.6Z"/></svg>',
        'chevrons-up-down' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m7 15 5 5 5-5M7 9l5-5 5 5"/></svg>',
    ];
    $tileClass = static function (?string $tile, string $name): string {
        return $tile === $name ? 'tile is-active' : 'tile';
    };
@endphp

@once
    <style>
        .page.prototype-dashboard-page {
            --bg: #F9F8F6;
            --surface: #FBFAF7;
            --surface-2: #F4F1EC;
            --surface-3: #EFEAE3;
            --border: rgba(30, 24, 16, 0.10);
            --border-strong: rgba(30, 24, 16, 0.16);
            --hairline: rgba(30, 24, 16, 0.08);
            --text: #1B1A17;
            --text-muted: #6B6660;
            --text-subtle: #A39E96;
            --black: #111110;
            --accent-pink: #F2546B;
            --tile-purple: #C8B8D6;
            --tile-purple-2: #A99BBC;
            --tile-grey: #B8B0A6;
            --tile-grey-2: #8E867C;
            --shadow-sm: 0 1px 0 rgba(30,24,16,0.04);
            --shadow-md: 0 1px 2px rgba(30,24,16,0.06), 0 0 0 0.5px rgba(30,24,16,0.06);
            --shadow-lg: 0 8px 24px rgba(30,24,16,0.08), 0 1px 2px rgba(30,24,16,0.06);
            min-height: 100vh;
            padding: 0;
            background: var(--bg);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
        }
        .page.prototype-dashboard-page * { box-sizing: border-box; }
        .prototype-app { background: var(--bg); display: grid; grid-template-columns: auto 1fr; min-height: 100vh; position: relative; }
        .rail { width: 56px; border-right: 0.5px solid var(--hairline); padding: 14px 0; display: flex; flex-direction: column; align-items: center; justify-content: space-between; background: var(--bg); }
        .rail-top, .rail-bottom { display: flex; flex-direction: column; align-items: center; gap: 16px; }
        .rail-icon { width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; color: #6B6660; cursor: pointer; border-radius: 6px; background: transparent; border: 0; padding: 0; position: relative; text-decoration: none; }
        .rail-icon:hover { color: var(--text); background: var(--surface-2); }
        .rail-icon svg, .tile-art svg, .status-pill svg, .sidepane-search svg, .sidepane-user-caret svg, .sidepane-upgrade-icon svg { width: 18px; height: 18px; stroke: currentColor; stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round; }
        .rail-add { background: #ECE6FA; color: #5B45C9; border: 0.5px solid #C9BCF0; }
        .rail-tooltip { position: absolute; left: calc(100% + 10px); top: 50%; transform: translateY(-50%); background: #fff; border: 0.5px solid var(--border); border-radius: 8px; padding: 5px 10px; font-size: 12px; color: var(--text); white-space: nowrap; box-shadow: var(--shadow-md); opacity: 0; pointer-events: none; transition: opacity .12s ease; }
        .rail-add:hover .rail-tooltip { opacity: 1; }
        .rail-avatar { width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(160deg, #7C5BE0, #5B3FC9); color: #fff; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; }
        .main { display: flex; flex-direction: column; min-width: 0; }
        .topbar { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 16px; padding: 14px 20px; border-bottom: 0.5px solid var(--hairline); background: var(--bg); }
        .brand { display: inline-flex; align-items: center; gap: 10px; padding: 6px 12px 6px 8px; background: var(--surface); border: 0.5px solid var(--border); border-radius: 999px; box-shadow: var(--shadow-sm); font-weight: 600; font-size: 13px; color: var(--text); white-space: nowrap; text-decoration: none; }
        .brand-mark { width: 18px; height: 18px; border-radius: 5px; background: var(--accent-pink); box-shadow: inset 0 0 0 0.5px rgba(0,0,0,0.06); }
        .search { display: flex; align-items: center; gap: 10px; height: 36px; padding: 0 14px; background: var(--surface); border: 0.5px solid var(--border); border-radius: 999px; box-shadow: var(--shadow-sm); max-width: 560px; width: 100%; justify-self: start; margin-left: 4px; }
        .search-dot { width: 6px; height: 6px; border-radius: 50%; background: #1B1A17; flex: 0 0 auto; }
        .search input { flex: 1; border: 0; outline: 0; background: transparent; font: inherit; color: var(--text); font-size: 13px; }
        .search input::placeholder { color: var(--text-subtle); }
        .search-kbd { font-size: 11px; color: var(--text-subtle); border: 0.5px solid var(--border); border-radius: 6px; padding: 2px 7px; line-height: 1; letter-spacing: 0.02em; }
        .topbar-right { display: inline-flex; align-items: center; gap: 10px; }
        .status-pill { display: inline-flex; align-items: center; gap: 10px; padding: 6px 14px 6px 10px; background: var(--surface); border: 0.5px solid var(--border); border-radius: 999px; box-shadow: var(--shadow-sm); font-size: 12.5px; color: var(--text); white-space: nowrap; text-decoration: none; }
        .bell-wrap { position: relative; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; color: #4D4944; }
        .bell-badge { position: absolute; top: -2px; right: -2px; min-width: 14px; height: 14px; padding: 0 3px; border-radius: 999px; background: var(--accent-pink); color: #fff; font-size: 9px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; line-height: 1; border: 1.5px solid var(--surface); }
        .content { flex: 1; display: grid; grid-template-columns: 140px 1fr; min-height: 0; position: relative; }
        .tile-rail { padding: 24px 16px; display: flex; flex-direction: column; gap: 24px; align-items: center; }
        .tile { width: 92px; display: flex; flex-direction: column; align-items: center; gap: 8px; cursor: pointer; text-decoration: none; color: inherit; }
        .tile-art { width: 88px; height: 88px; border-radius: 18px; display: flex; align-items: center; justify-content: center; color: #fff; box-shadow: inset 0 1px 0 rgba(255,255,255,0.35), inset 0 -10px 24px rgba(0,0,0,0.12), 0 1px 2px rgba(30,24,16,0.08); position: relative; overflow: hidden; }
        .tile-art svg { width: 28px; height: 28px; stroke-width: 1.8; position: relative; z-index: 1; }
        .tile-art::after { content: ""; position: absolute; inset: 0; background: linear-gradient(160deg, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0) 45%, rgba(0,0,0,0.10) 100%); pointer-events: none; }
        .tile-art.purple { background: linear-gradient(160deg, var(--tile-purple) 0%, var(--tile-purple-2) 100%); }
        .tile-art.grey { background: linear-gradient(160deg, var(--tile-grey) 0%, var(--tile-grey-2) 100%); }
        .tile-label { font-size: 12px; color: var(--text); font-weight: 500; text-align: center; }
        .tile.is-active .tile-label { font-weight: 700; }
        .workspace { padding: 28px 40px 60px; display: flex; flex-direction: column; min-height: 100%; position: relative; }
        .workspace-header { display: flex; justify-content: flex-end; align-items: center; margin-bottom: 28px; min-height: 40px; }
        .new-project-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; background: var(--black); color: #fff; border: 0; border-radius: 999px; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; flex: 0 0 auto; text-decoration:none; box-shadow: 0 4px 12px rgba(17,17,16,0.18), 0 1px 0 rgba(255,255,255,0.08) inset; }
        .new-project-btn:hover { background: #000; }
        .new-project-btn .plus { font-size: 14px; line-height: 1; font-weight: 500; margin-right: -2px; }
        .divider { height: 0; border-top: 0.5px solid var(--border-strong); margin: 0; width: 60%; align-self: center; }
        .empty-state { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; text-align: center; padding-top: 40px; gap: 4px; }
        .empty-state h2 { margin: 0; font-size: 14px; font-weight: 600; color: var(--text); letter-spacing: -0.005em; white-space: nowrap; }
        .empty-state p { margin: 0; font-size: 13px; color: var(--text-subtle); font-weight: 400; white-space: nowrap; }
        .project-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 260px)); gap:18px; padding-top:34px; }
        .project-card { display:flex; flex-direction:column; gap:14px; text-decoration:none; color:inherit; background:var(--surface); border:0.5px solid var(--border); border-radius:18px; padding:18px; box-shadow:var(--shadow-md); }
        .project-card-art { width:100%; aspect-ratio:1.2 / 1; border-radius:18px; background:linear-gradient(160deg, rgba(200,184,214,0.95), rgba(169,155,188,0.96)); box-shadow: inset 0 1px 0 rgba(255,255,255,0.35), inset 0 -10px 24px rgba(0,0,0,0.12); display:flex; align-items:center; justify-content:center; }
        .folder { width:34px; height:24px; border-radius:8px 8px 6px 6px; border:2px solid rgba(255,255,255,0.95); position:relative; }
        .folder::before { content:""; position:absolute; width:16px; height:6px; border-radius:6px 6px 0 0; border:2px solid rgba(255,255,255,0.95); border-bottom:0; left:2px; top:-8px; }
        .project-card-title { margin:0; font-size:16px; font-weight:600; letter-spacing:-0.01em; }
        .project-card-meta { margin:4px 0 0; font-size:12.5px; color:var(--text-subtle); }
        .workspace-window { width:min(920px, calc(100% - 80px)); margin:36px auto 0; background:var(--surface); border:0.5px solid var(--border); border-radius:18px; box-shadow:var(--shadow-lg); overflow:hidden; }
        .workspace-window-header { display:flex; align-items:center; justify-content:center; position:relative; padding:14px 20px; border-bottom:0.5px solid var(--hairline); }
        .traffic { position:absolute; left:18px; display:inline-flex; gap:7px; align-items:center; }
        .traffic span { width:12px; height:12px; border-radius:50%; display:inline-block; box-shadow: inset 0 0 0 0.5px rgba(0,0,0,0.10); }
        .traffic .red { background:#ED6A5E; }
        .traffic .yellow { background:#F4BF4F; }
        .traffic .green { background:#62C554; }
        .workspace-window-title { font-size:11px; font-weight:600; letter-spacing:0.10em; text-transform:uppercase; color:var(--text-muted); }
        .workspace-window-body { padding:28px 28px 30px; }
        .sidepane-user-caret, .sidepane-upgrade-icon { display:inline-flex; align-items:center; justify-content:center; }
        @media (max-width: 980px) {
            .content { grid-template-columns: 1fr; }
            .tile-rail { flex-direction: row; justify-content: center; padding: 20px; }
            .workspace { padding: 20px 20px 60px; }
            .workspace-window { width: calc(100% - 20px); }
        }
    </style>
@endonce

<div {{ $attributes->merge(['class' => 'prototype-app']) }}>
    <aside class="rail" id="leftRail">
        <div class="rail-top">
            @if($showSidepane)
                <button type="button" class="rail-icon" id="openSidebarBtn" aria-label="Open sidebar">{!! $icons['panel-left'] !!}</button>
            @else
                <a href="{{ route('dashboard') }}" class="rail-icon" aria-label="Dashboard">{!! $icons['panel-left'] !!}</a>
            @endif
            <a href="{{ route('founder.settings') }}" class="rail-icon" aria-label="Settings">{!! $icons['settings'] !!}</a>
            @if($showAiToolsButton)
                @if($aiToolsMode === 'overlay')
                    <button type="button" class="rail-icon rail-add" id="railAiToolsBtn" aria-label="New Agent">{!! $icons['plus'] !!}<span class="rail-tooltip">New Agent</span></button>
                @else
                    <a href="{{ route('founder.ai-tools') }}" class="rail-icon rail-add" aria-label="New Agent">{!! $icons['plus'] !!}<span class="rail-tooltip">New Agent</span></a>
                @endif
            @endif
        </div>
        <div class="rail-bottom">
            <a href="{{ route('founder.inbox') }}" class="rail-icon" aria-label="Inbox">{!! $icons['inbox'] !!}</a>
            <span class="rail-avatar">{{ $founderInitial }}</span>
        </div>
    </aside>

    @if($showSidepane)
        <aside class="sidepane" id="sidepane">
            <div class="sidepane-head">
                <button type="button" class="sidepane-close" id="closeSidebarBtn" aria-label="Collapse sidebar">{!! $icons['panel-left'] !!}</button>
            </div>

            <div class="sidepane-segment">
                <button type="button" class="seg-btn">Browse</button>
                <button type="button" class="seg-btn is-active">Agent <span class="seg-badge">NEW</span></button>
            </div>

            <a href="{{ route('founder.settings') }}" class="sidepane-row">Customize <span class="seg-badge">NEW</span></a>
            <button type="button" class="sidepane-row" id="sidepaneNewAgentBtn">{!! $icons['plus'] !!} New Agent</button>

            <div class="sidepane-search">
                <span>{!! $icons['search'] !!}</span>
                <input type="text" placeholder="Search chats…">
            </div>

            <div class="sidepane-section-label">Recent</div>
            <div class="sidepane-recent">
                @forelse($recentItems as $recentItem)
                    <button type="button" class="sidepane-recent-item">{{ $recentItem }}</button>
                @empty
                    <button type="button" class="sidepane-recent-item">{{ $founderName }}</button>
                @endforelse
            </div>

            <div class="sidepane-spacer"></div>

            <div class="sidepane-upgrade">
                <div>
                    <div class="sidepane-upgrade-title">Upgrade</div>
                    <div class="sidepane-upgrade-sub">Unlock unlimited generations</div>
                </div>
                <span class="sidepane-upgrade-icon">{!! $icons['sparkles'] !!}</span>
            </div>

            <a href="{{ route('founder.notifications') }}" class="sidepane-row sidepane-news">What's new <span class="sidepane-news-dot"></span></a>

            <div class="sidepane-user">
                <span class="rail-avatar">{{ $founderInitial }}</span>
                <div class="sidepane-user-info">
                    <div class="sidepane-user-name">{{ $founderName }}</div>
                    <div class="sidepane-user-email">{{ $founder->email ?? '' }}</div>
                </div>
                <span class="sidepane-user-caret">{!! $icons['chevrons-up-down'] !!}</span>
            </div>
        </aside>
    @endif

    <div class="main">
        <div class="topbar">
            <a href="{{ route('dashboard') }}" class="brand">
                <span class="brand-mark"></span>
                <span>Hatchers AI OS</span>
            </a>

            <div class="search">
                <span class="search-dot"></span>
                <input type="text" placeholder="{{ $searchPlaceholder }}">
                <span class="search-kbd">⌘K</span>
            </div>

            <div class="topbar-right">
                <a href="{{ route('founder.notifications') }}" class="status-pill">
                    <span class="bell-wrap">
                        {!! $icons['bell'] !!}
                        @if($unreadCount > 0)
                            <span class="bell-badge">{{ $unreadCount }}</span>
                        @endif
                    </span>
                    <span>{{ $statusLabel }}</span>
                </a>
            </div>
        </div>

        <div class="content">
            <div class="tile-rail">
                <a class="{{ $tileClass($activeTile, 'tasks') }}" href="{{ route('founder.tasks') }}">
                    <div class="tile-art purple">{!! $icons['list'] !!}</div>
                    <div class="tile-label">Tasks</div>
                </a>
                <a class="{{ $tileClass($activeTile, 'inbox') }}" href="{{ route('founder.inbox') }}">
                    <div class="tile-art grey">{!! $icons['inbox'] !!}</div>
                    <div class="tile-label">Inbox</div>
                </a>
                @if($aiToolsMode === 'overlay')
                    <button class="{{ $tileClass($activeTile, 'ai-tools') }}" type="button" id="openToolsBtn" style="border:0;background:transparent;padding:0;">
                        <div class="tile-art grey">{!! $icons['sparkles'] !!}</div>
                        <div class="tile-label">AI Tools</div>
                    </button>
                @else
                    <a class="{{ $tileClass($activeTile, 'ai-tools') }}" href="{{ route('founder.ai-tools') }}">
                        <div class="tile-art grey">{!! $icons['sparkles'] !!}</div>
                        <div class="tile-label">AI Tools</div>
                    </a>
                @endif
            </div>

            {{ $slot }}
        </div>
    </div>

    @isset($afterMain)
        {{ $afterMain }}
    @endisset
</div>
