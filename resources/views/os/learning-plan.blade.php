@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .learning-shell { min-height: 100vh; display: grid; grid-template-columns: 220px minmax(0, 1fr) 220px; background: #f8f5ee; }
        .learning-sidebar, .learning-rightbar { background: rgba(255, 252, 247, 0.8); border-color: var(--line); border-style: solid; border-width: 0 1px 0 0; min-height: 100vh; display: flex; flex-direction: column; }
        .learning-rightbar { border-width: 0 0 0 1px; background: rgba(255, 251, 246, 0.9); }
        .learning-sidebar-inner, .learning-rightbar-inner { padding: 22px 18px; }
        .learning-brand { display: inline-block; margin-bottom: 24px; }
        .learning-brand img { width: 168px; height: auto; display: block; }
        .learning-nav { display: grid; gap: 6px; }
        .learning-nav-item { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 14px; text-decoration: none; color: var(--ink); font-size: 0.98rem; }
        .learning-nav-item.active { background: #ece6db; }
        .learning-nav-icon { width: 18px; text-align: center; color: var(--muted); }
        .learning-sidebar-footer { margin-top: auto; padding: 18px; border-top: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .learning-user { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .learning-avatar, .drawer-avatar { width: 30px; height: 30px; border-radius: 999px; background: #b0a999; color: #fff; display: grid; place-items: center; font-weight: 700; font-size: 0.92rem; flex-shrink: 0; }
        .learning-main { padding: 26px 28px 24px; }
        .learning-main-inner { max-width: 700px; margin: 0 auto; }
        .learning-main h1 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing: -0.02em; margin-bottom: 6px; }
        .learning-main p { color: var(--muted); margin-bottom: 24px; }
        .learning-card { background: rgba(255,255,255,0.92); border: 1px solid rgba(220, 207, 191, 0.65); border-radius: 18px; padding: 18px 20px; box-shadow: 0 10px 28px rgba(52, 41, 26, 0.04); margin-bottom: 12px; cursor: pointer; }
        .learning-card-top { display:flex; align-items:center; justify-content:space-between; gap:16px; }
        .learning-card-actions { display:flex; align-items:flex-end; gap:10px; flex-direction:column; }
        .learning-meta { font-size: 0.84rem; color: #4c91ec; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
        .learning-subtle { color: var(--muted); font-size: 0.98rem; margin-top: 4px; }
        .learning-badge { padding: 8px 14px; border-radius: 10px; background: #f0ece4; color: #7a7267; font-size: 0.95rem; white-space: nowrap; }
        .learning-status { border:1px solid rgba(220,207,191,0.9); border-radius:10px; padding:10px 14px; background:#fffdf8; color:var(--ink); font-weight:600; cursor:pointer; }
        .learning-banner { border-radius:14px; padding:14px 16px; margin-bottom:16px; font-size:0.96rem; }
        .learning-banner.success { background:rgba(78, 188, 118, 0.12); border:1px solid rgba(78, 188, 118, 0.24); color:#21643a; }
        .learning-banner.error { background:rgba(199, 63, 63, 0.08); border:1px solid rgba(199, 63, 63, 0.2); color:#8c2d2d; }
        .learning-rightbar h3 { font-size: 0.83rem; letter-spacing: 0.06em; text-transform: uppercase; color: var(--muted); margin-bottom: 12px; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; font-size: 0.84rem; color: var(--muted); }
        .calendar-head { text-align:center; font-size: 0.74rem; text-transform: uppercase; }
        .calendar-day { width:28px; height:28px; margin:0 auto; border-radius:999px; display:grid; place-items:center; color:var(--ink); }
        .calendar-day.dim { color:#b9b1a5; }
        .calendar-day.today { background:#6d675f; color:white; }
        .tool-list { display:grid; gap:10px; margin-top:14px; }
        .tool-item { display:flex; align-items:center; gap:12px; font-weight:600; background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:14px; padding:12px 14px; }
        .tool-icon { width:28px; height:28px; border-radius:8px; border:1px solid rgba(222,60,109,0.24); color:#e02961; display:grid; place-items:center; font-size:0.82rem; }
        .mini-note { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:14px; padding:12px 14px; margin-top:14px; }
        .learning-drawer { position: fixed; top:0; right:0; width:min(480px,100%); height:100vh; background:#fffdf8; border-left:1px solid rgba(220,207,191,0.8); box-shadow:-10px 0 30px rgba(52,41,26,0.08); transform:translateX(100%); transition:transform 0.25s ease; z-index:40; display:flex; flex-direction:column; }
        .learning-drawer.open { transform: translateX(0); }
        .learning-drawer-header { padding:20px 24px 12px; display:flex; align-items:flex-start; justify-content:space-between; gap:14px; }
        .learning-drawer-body { padding:0 24px 24px; overflow-y:auto; }
        .learning-drawer-close { border:0; background:transparent; font-size:1.6rem; color:var(--muted); cursor:pointer; line-height:1; }
        .drawer-eyebrow { font-size:0.8rem; text-transform:uppercase; letter-spacing:0.08em; color:#4c91ec; margin-bottom:10px; }
        .drawer-badge { padding:8px 14px; border-radius:10px; background:#f0ece4; color:#7a7267; font-size:0.95rem; white-space:nowrap; display:inline-block; }
        .drawer-grid { display:grid; grid-template-columns:100px 1fr; gap:14px 10px; margin:24px 0; }
        .drawer-label { color:var(--muted); }
        .drawer-comment { display:flex; gap:12px; align-items:flex-start; background:white; border:1px solid rgba(220,207,191,0.65); border-radius:14px; padding:14px; margin-top:12px; }
        .drawer-actions { display:flex; align-items:center; gap:10px; margin:18px 0 8px; }
        @media (max-width:1240px) { .learning-shell { grid-template-columns:220px 1fr; } .learning-rightbar { display:none; } }
        @media (max-width:900px) { .learning-shell { grid-template-columns:1fr; } .learning-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .learning-sidebar-footer { display:none; } .learning-main { padding:20px 16px 24px; } }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $workspace = $dashboard['workspace'];
        $entries = $workspace['learning_plan_entries'];
        $calendar = $workspace['calendar'];
        $aiTools = $workspace['ai_tools'];
    @endphp

    <div class="learning-shell">
        <aside class="learning-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $founder->company->business_model ?? 'hybrid',
                'activeKey' => 'learning-plan',
                'navClass' => 'learning-nav',
                'itemClass' => 'learning-nav-item',
                'iconClass' => 'learning-nav-icon',
                'innerClass' => 'learning-sidebar-inner',
                'brandClass' => 'learning-brand',
                'footerClass' => 'learning-sidebar-footer',
                'userClass' => 'learning-user',
                'avatarClass' => 'learning-avatar',
            ])
        </aside>

        <main class="learning-main">
            <div class="learning-main-inner">
                <h1>Learning Plan</h1>
                <p>Your weekly lessons and guided founder prompts, all inside Hatchers Ai OS.</p>
                @if (session('success'))
                    <div class="learning-banner success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="learning-banner error">{{ session('error') }}</div>
                @endif
                @foreach ($entries as $entry)
                    <article class="learning-card"
                        data-open-learning
                        data-status-url="{{ !empty($entry['id']) ? route('founder.learning-plan.status', $entry['id']) : '' }}"
                        data-status-value="{{ !empty($entry['completed']) ? 'pending' : 'completed' }}"
                        data-status-label="{{ e($entry['status_label'] ?? '') }}"
                        data-drawer-title="{{ e($entry['detail_heading']) }}"
                        data-drawer-due="{{ e($entry['detail_due']) }}"
                        data-drawer-owner="{{ e($entry['detail_owner']) }}"
                        data-drawer-description="{{ e($entry['detail_description']) }}"
                        data-drawer-badge="{{ e($entry['badge']) }}"
                        data-drawer-comments='@json($entry["comments"])'>
                        <div class="learning-card-top">
                            <div>
                                <div class="learning-meta">LESSON</div>
                                <div style="font-size:1.1rem;font-weight:600;">{{ $entry['title'] }}</div>
                                <div class="learning-subtle">{{ $entry['subtitle'] }}</div>
                                @if (!empty($entry['mentor_name']))
                                    <div class="learning-subtle" style="margin-top:8px;">Mentor linked: {{ $entry['mentor_name'] }}</div>
                                @endif
                            </div>
                            <div class="learning-card-actions">
                                <div class="learning-badge">{{ $entry['badge'] }}</div>
                                @if (!empty($entry['id']) && !empty($entry['status_label']))
                                    <form method="POST" action="{{ route('founder.learning-plan.status', $entry['id']) }}" onclick="event.stopPropagation();" style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ !empty($entry['completed']) ? 'pending' : 'completed' }}">
                                        <button class="learning-status" type="submit">{{ $entry['status_label'] }}</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </main>

        <aside class="learning-rightbar">
            <div class="learning-rightbar-inner">
                <h3>Calendar</h3>
                <div style="margin-bottom:14px;color:var(--muted);font-size:0.86rem;">{{ $calendar['month_label'] }}</div>
                <div class="calendar-grid">
                    @foreach (['S','M','T','W','T','F','S'] as $dayLabel)
                        <div class="calendar-head">{{ $dayLabel }}</div>
                    @endforeach
                    @foreach ($calendar['days'] as $day)
                        <div class="calendar-day {{ !$day['in_month'] ? 'dim' : '' }} {{ $day['is_today'] ? 'today' : '' }}">{{ $day['day'] }}</div>
                    @endforeach
                </div>
                <div class="mini-note">Learning tasks stay synced with your weekly founder plan and mentor context.</div>

                <h3 id="learning-tools" style="margin-top:22px;">AI Tools</h3>
                <div class="tool-list">
                    @foreach ($aiTools as $tool)
                        <div class="tool-item"><div class="tool-icon">□</div><div>{{ $tool['title'] }}</div></div>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>

    <aside class="learning-drawer" data-learning-drawer>
        <div class="learning-drawer-header">
            <div>
                <div class="drawer-eyebrow">Lesson</div>
                <h2 data-drawer-title style="font-size:1.7rem;margin:0;">Lesson</h2>
            </div>
            <button class="learning-drawer-close" type="button" data-close-learning>&times;</button>
        </div>
        <div class="learning-drawer-body">
            <div class="drawer-badge" data-drawer-badge>Open</div>
            <div class="drawer-actions">
                <form method="POST" action="" data-drawer-status-form style="display:none;margin:0;">
                    @csrf
                    <input type="hidden" name="status" value="" data-drawer-status-input>
                    <button class="learning-status" type="submit" data-drawer-status-label>Update lesson</button>
                </form>
            </div>
            <div class="drawer-grid">
                <div class="drawer-label">Due date</div>
                <div data-drawer-due></div>
                <div class="drawer-label">Owner</div>
                <div data-drawer-owner></div>
                <div class="drawer-label">Description</div>
                <div data-drawer-description></div>
            </div>
            <div class="drawer-label" style="margin-bottom:10px;">Comments</div>
            <div data-drawer-comments></div>
        </div>
    </aside>
@endsection

@section('scripts')
    <script>
        (() => {
            const drawer = document.querySelector('[data-learning-drawer]');
            if (!drawer) return;
            const title = drawer.querySelector('[data-drawer-title]');
            const due = drawer.querySelector('[data-drawer-due]');
            const owner = drawer.querySelector('[data-drawer-owner]');
            const description = drawer.querySelector('[data-drawer-description]');
            const badge = drawer.querySelector('[data-drawer-badge]');
            const comments = drawer.querySelector('[data-drawer-comments]');
            const statusForm = drawer.querySelector('[data-drawer-status-form]');
            const statusInput = drawer.querySelector('[data-drawer-status-input]');
            const statusLabel = drawer.querySelector('[data-drawer-status-label]');
            document.querySelectorAll('[data-open-learning]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    title.textContent = trigger.getAttribute('data-drawer-title') || 'Lesson';
                    due.textContent = trigger.getAttribute('data-drawer-due') || '';
                    owner.textContent = trigger.getAttribute('data-drawer-owner') || '';
                    description.textContent = trigger.getAttribute('data-drawer-description') || '';
                    badge.textContent = trigger.getAttribute('data-drawer-badge') || 'Open';
                    const statusUrl = trigger.getAttribute('data-status-url') || '';
                    const nextStatus = trigger.getAttribute('data-status-value') || '';
                    const nextStatusLabel = trigger.getAttribute('data-status-label') || '';
                    if (statusUrl && nextStatus && nextStatusLabel) {
                        statusForm.style.display = '';
                        statusForm.setAttribute('action', statusUrl);
                        statusInput.value = nextStatus;
                        statusLabel.textContent = nextStatusLabel;
                    } else {
                        statusForm.style.display = 'none';
                        statusForm.setAttribute('action', '');
                        statusInput.value = '';
                        statusLabel.textContent = 'Update lesson';
                    }
                    let parsed = [];
                    try { parsed = JSON.parse(trigger.getAttribute('data-drawer-comments') || '[]'); } catch (error) {}
                    comments.innerHTML = '';
                    parsed.forEach((comment) => {
                        const node = document.createElement('div');
                        node.className = 'drawer-comment';
                        node.innerHTML = `<div class="drawer-avatar">${(comment.author || 'U').slice(0,1).toUpperCase()}</div><div><div style="font-weight:600;margin-bottom:4px;">${comment.author || 'Founder'}</div><div>${comment.message || ''}</div></div>`;
                        comments.appendChild(node);
                    });
                    drawer.classList.add('open');
                });
            });
            drawer.querySelector('[data-close-learning]')?.addEventListener('click', () => drawer.classList.remove('open'));
        })();
    </script>
@endsection
