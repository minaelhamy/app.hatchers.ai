@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .atlas-frame-shell { min-height: 100vh; display:grid; grid-template-columns:220px minmax(0,1fr); background:#f8f5ee; }
        .atlas-frame-sidebar { background: rgba(255,252,247,0.8); border-right:1px solid var(--line); min-height:100vh; display:flex; flex-direction:column; }
        .atlas-frame-sidebar-inner { padding:22px 18px; }
        .atlas-frame-brand { display:inline-block; margin-bottom:24px; }
        .atlas-frame-brand img { width:168px; height:auto; display:block; }
        .atlas-frame-nav { display:grid; gap:6px; }
        .atlas-frame-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .atlas-frame-nav-item.active { background:#ece6db; }
        .atlas-frame-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .atlas-frame-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .atlas-frame-user { display:flex; align-items:center; gap:10px; }
        .atlas-frame-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .atlas-frame-main { padding:22px; display:grid; gap:16px; }
        .atlas-frame-header { display:flex; align-items:flex-start; justify-content:space-between; gap:18px; background:rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:18px 20px; }
        .atlas-frame-header h1 { margin:0 0 6px; font-size:1.6rem; }
        .atlas-frame-header p { margin:0; color:var(--muted); }
        .atlas-frame-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .atlas-frame-button { display:inline-block; padding:10px 14px; border-radius:12px; text-decoration:none; font-weight:600; background:#f0ece4; color:#5d554a; }
        .atlas-frame-button.primary { background:linear-gradient(90deg,#8e1c74,#ff2c35); color:#fff; }
        .atlas-frame-panel { min-height:78vh; background:rgba(255,255,255,0.96); border:1px solid rgba(220,207,191,0.65); border-radius:22px; overflow:hidden; box-shadow:0 12px 32px rgba(52,41,26,0.06); }
        .atlas-frame-panel iframe { display:block; width:100%; min-height:78vh; border:0; background:#fff; }
        @media (max-width:900px) {
            .atlas-frame-shell { grid-template-columns:1fr; }
            .atlas-frame-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); }
            .atlas-frame-footer { display:none; }
            .atlas-frame-main { padding:16px; }
            .atlas-frame-header { flex-direction:column; }
        }
    </style>
@endsection

@section('content')
    @php $founder = $dashboard['founder']; @endphp

    <div class="atlas-frame-shell">
        <aside class="atlas-frame-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $founder->company->business_model ?? 'hybrid',
                'activeKey' => 'ai-tools',
                'navClass' => 'atlas-frame-nav',
                'itemClass' => 'atlas-frame-nav-item',
                'iconClass' => 'atlas-frame-nav-icon',
                'innerClass' => 'atlas-frame-sidebar-inner',
                'brandClass' => 'atlas-frame-brand',
                'footerClass' => 'atlas-frame-footer',
                'userClass' => 'atlas-frame-user',
                'avatarClass' => 'atlas-frame-avatar',
            ])
        </aside>

        <main class="atlas-frame-main">
            <section class="atlas-frame-header">
                <div>
                    <h1>{{ $workspaceLabel }}</h1>
                    <p>Stay inside Hatchers OS while using the real Atlas workspace underneath. This keeps campaigns, chats, and media work in one operating surface.</p>
                </div>
                <div class="atlas-frame-actions">
                    <a class="atlas-frame-button" href="{{ route('founder.ai-tools') }}">Back to AI Studio</a>
                    <a class="atlas-frame-button primary" href="{{ $proxyUrl }}" target="_blank" rel="noopener">Open in new tab</a>
                </div>
            </section>

            <section class="atlas-frame-panel">
                <iframe src="{{ $proxyUrl }}" title="{{ $workspaceLabel }}"></iframe>
            </section>
        </main>
    </div>
@endsection
