@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .tasks-shell { min-height: 100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 220px; background:#f8f5ee; }
        .tasks-sidebar, .tasks-rightbar { background: rgba(255,252,247,0.8); border-color: var(--line); border-style: solid; border-width: 0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .tasks-rightbar { border-width:0 0 0 1px; background: rgba(255,251,246,0.9); }
        .tasks-sidebar-inner, .tasks-rightbar-inner { padding:22px 18px; }
        .tasks-brand { display:inline-block; margin-bottom:24px; }
        .tasks-brand img { width:168px; height:auto; display:block; }
        .tasks-nav { display:grid; gap:6px; }
        .tasks-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .tasks-nav-item.active { background:#ece6db; }
        .tasks-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .tasks-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .tasks-user { display:flex; align-items:center; gap:10px; }
        .tasks-avatar, .task-drawer-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .tasks-main { padding:26px 28px 24px; }
        .tasks-main-inner { max-width:700px; margin:0 auto; }
        .tasks-main h1 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing:-0.02em; margin-bottom:6px; }
        .tasks-main p { color: var(--muted); margin-bottom:24px; }
        .task-card { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:18px 20px; box-shadow:0 10px 28px rgba(52,41,26,0.04); margin-bottom:12px; cursor:pointer; }
        .task-card-top { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:8px; }
        .task-card-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .task-label { font-size:0.83rem; color:var(--rose); text-transform:uppercase; letter-spacing:0.05em; }
        .task-status { border:1px solid rgba(220,207,191,0.9); border-radius:10px; padding:10px 14px; background:#fffdf8; color:var(--ink); font-weight:600; cursor:pointer; }
        .task-title { font-size:1.08rem; font-weight:600; }
        .task-subtle { color: var(--muted); font-size:0.98rem; margin-top:4px; }
        .task-card.completed .task-title { text-decoration: line-through; opacity:0.75; }
        .task-card.completed .task-label, .task-card.completed .task-subtle { opacity:0.72; }
        .task-banner { border-radius:14px; padding:14px 16px; margin-bottom:16px; font-size:0.96rem; }
        .task-banner.success { background:rgba(78, 188, 118, 0.12); border:1px solid rgba(78, 188, 118, 0.24); color:#21643a; }
        .task-banner.error { background:rgba(199, 63, 63, 0.08); border:1px solid rgba(199, 63, 63, 0.2); color:#8c2d2d; }
        .tasks-rightbar h3 { font-size:0.83rem; letter-spacing:0.06em; text-transform:uppercase; color:var(--muted); margin-bottom:12px; }
        .mini-note, .tool-item { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:14px; padding:12px 14px; }
        .tool-list { display:grid; gap:10px; margin-top:14px; }
        .tool-item { display:flex; align-items:center; gap:12px; font-weight:600; }
        .tool-icon { width:28px; height:28px; border-radius:8px; border:1px solid rgba(222,60,109,0.24); color:#e02961; display:grid; place-items:center; font-size:0.82rem; }
        .task-drawer { position: fixed; top:0; right:0; width:min(480px,100%); height:100vh; background:#fffdf8; border-left:1px solid rgba(220,207,191,0.8); box-shadow:-10px 0 30px rgba(52,41,26,0.08); transform:translateX(100%); transition:transform 0.25s ease; z-index:40; display:flex; flex-direction:column; }
        .task-drawer.open { transform:translateX(0); }
        .task-drawer-header { padding:20px 24px 12px; display:flex; align-items:flex-start; justify-content:space-between; gap:14px; }
        .task-drawer-body { padding:0 24px 24px; overflow-y:auto; }
        .task-drawer-close { border:0; background:transparent; font-size:1.6rem; color:var(--muted); cursor:pointer; line-height:1; }
        .task-eyebrow { font-size:0.8rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--rose); margin-bottom:10px; }
        .task-badge { padding:8px 14px; border-radius:10px; background:#f0ece4; color:#7a7267; font-size:0.95rem; display:inline-block; }
        .task-drawer-grid { display:grid; grid-template-columns:100px 1fr; gap:14px 10px; margin:24px 0; }
        .task-drawer-label { color:var(--muted); }
        .task-comment { display:flex; gap:12px; align-items:flex-start; background:white; border:1px solid rgba(220,207,191,0.65); border-radius:14px; padding:14px; margin-top:12px; }
        .task-drawer-actions { display:flex; align-items:center; gap:10px; margin:18px 0 8px; }
        @media (max-width:1240px) { .tasks-shell { grid-template-columns:220px 1fr; } .tasks-rightbar { display:none; } }
        @media (max-width:900px) { .tasks-shell { grid-template-columns:1fr; } .tasks-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line);} .tasks-sidebar-footer { display:none; } .tasks-main { padding:20px 16px 24px; } }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $workspace = $dashboard['workspace'];
        $tasks = $workspace['task_center_entries'];
        $aiTools = $workspace['ai_tools'];
    @endphp

    <div class="tasks-shell">
        <aside class="tasks-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $founder->company->business_model ?? 'hybrid',
                'activeKey' => 'tasks',
                'navClass' => 'tasks-nav',
                'itemClass' => 'tasks-nav-item',
                'iconClass' => 'tasks-nav-icon',
                'innerClass' => 'tasks-sidebar-inner',
                'brandClass' => 'tasks-brand',
                'footerClass' => 'tasks-sidebar-footer',
                'userClass' => 'tasks-user',
                'avatarClass' => 'tasks-avatar',
            ])
        </aside>

        <main class="tasks-main">
            <div class="tasks-main-inner">
                <h1>Tasks</h1>
                <p>Your single daily execution center. If you want to know what to do next, start here.</p>
                @if (session('success'))
                    <div class="task-banner success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="task-banner error">{{ session('error') }}</div>
                @endif
                @foreach ($tasks as $task)
                    <article class="task-card {{ $task['completed'] ? 'completed' : '' }}"
                        data-open-task
                        data-status-url="{{ $task['id'] ? route('founder.tasks.status', $task['id']) : '' }}"
                        data-status-value="{{ $task['completed'] ? 'pending' : 'completed' }}"
                        data-status-label="{{ e($task['status_label'] ?? '') }}"
                        data-drawer-title="{{ e($task['detail_heading']) }}"
                        data-drawer-due="{{ e($task['detail_due']) }}"
                        data-drawer-owner="{{ e($task['detail_owner']) }}"
                        data-drawer-description="{{ e($task['detail_description']) }}"
                        data-drawer-badge="{{ e($task['completed'] ? 'Completed' : 'Open') }}"
                        data-drawer-comments='@json($task["comments"])'>
                        <div class="task-card-top">
                            <div class="task-label">{{ strtoupper($task['label']) }} · {{ strtoupper($task['due']) }}</div>
                            <div class="task-card-actions">
                                @if ($task['id'])
                                    <form method="POST" action="{{ route('founder.tasks.status', $task['id']) }}" onclick="event.stopPropagation();" style="margin:0;" data-task-status-form>
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $task['completed'] ? 'pending' : 'completed' }}">
                                        <button class="task-status" type="submit" data-task-status-button>{{ $task['status_label'] }}</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        <div class="task-title">{{ $task['title'] }}</div>
                        <div class="task-subtle">{{ $task['description'] }}</div>
                        @if (!empty($task['mentor_name']))
                            <div class="task-subtle" style="margin-top:8px;">Mentor linked: {{ $task['mentor_name'] }} · {{ $task['mentor_context'] }}</div>
                        @endif
                    </article>
                @endforeach
            </div>
        </main>

        <aside class="tasks-rightbar">
            <div class="tasks-rightbar-inner">
                <h3>Task Guidance</h3>
                <div class="mini-note">Prioritize the tasks that move your website, offer, and customer pipeline forward this week.</div>
                <div class="mini-note" style="margin-top:10px;">Stay focused on the task itself here. Complete it, reopen it, and review the guidance without leaving this workspace.</div>
                @if (!empty($workspace['mentor_session']['subtitle']))
                    <div class="mini-note" style="margin-top:10px;">Mentor context: {{ $workspace['mentor_session']['subtitle'] }}. Tasks with mentor linkage are aligned to that rhythm.</div>
                @endif

                <h3 id="tasks-tools" style="margin-top:22px;">AI Tools</h3>
                <div class="tool-list">
                    @foreach ($aiTools as $tool)
                        <div class="tool-item"><div class="tool-icon">□</div><div>{{ $tool['title'] }}</div></div>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>

    <aside class="task-drawer" data-task-drawer>
        <div class="task-drawer-header">
            <div>
                <div class="task-eyebrow">Task</div>
                <h2 data-drawer-title style="font-size:1.7rem;margin:0;">Task</h2>
            </div>
            <button class="task-drawer-close" type="button" data-close-task>&times;</button>
        </div>
        <div class="task-drawer-body">
            <div class="task-badge" data-drawer-badge>Open</div>
            <div class="task-drawer-actions">
                <form method="POST" action="" data-drawer-status-form style="display:none;margin:0;">
                    @csrf
                    <input type="hidden" name="status" value="" data-drawer-status-input>
                    <button class="task-status" type="submit" data-drawer-status-label>Update task</button>
                </form>
            </div>
            <div class="task-drawer-grid">
                <div class="task-drawer-label">Due date</div>
                <div data-drawer-due></div>
                <div class="task-drawer-label">Milestone</div>
                <div data-drawer-owner></div>
                <div class="task-drawer-label">Description</div>
                <div data-drawer-description></div>
            </div>
            <div class="task-drawer-label" style="margin-bottom:10px;">Comments</div>
            <div data-drawer-comments></div>
        </div>
    </aside>
@endsection

@section('scripts')
    <script>
        (() => {
            const drawer = document.querySelector('[data-task-drawer]');
            if (!drawer) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const title = drawer.querySelector('[data-drawer-title]');
            const due = drawer.querySelector('[data-drawer-due]');
            const owner = drawer.querySelector('[data-drawer-owner]');
            const description = drawer.querySelector('[data-drawer-description]');
            const badge = drawer.querySelector('[data-drawer-badge]');
            const comments = drawer.querySelector('[data-drawer-comments]');
            const statusForm = drawer.querySelector('[data-drawer-status-form]');
            const statusInput = drawer.querySelector('[data-drawer-status-input]');
            const statusLabel = drawer.querySelector('[data-drawer-status-label]');
            let activeTaskCard = null;

            const applyTaskState = (trigger, completed) => {
                if (!trigger) return;
                const nextStatus = completed ? 'pending' : 'completed';
                const nextLabel = completed ? 'Reopen task' : 'Complete task';
                const dueLabel = completed ? 'COMPLETED' : 'OPEN';

                trigger.classList.toggle('completed', completed);
                trigger.setAttribute('data-status-value', nextStatus);
                trigger.setAttribute('data-status-label', nextLabel);
                trigger.setAttribute('data-drawer-badge', completed ? 'Completed' : 'Open');

                const cardLabel = trigger.querySelector('.task-label');
                if (cardLabel) {
                    const labelParts = (cardLabel.textContent || '').split('·');
                    const leftLabel = (labelParts[0] || 'TASK').trim();
                    cardLabel.textContent = `${leftLabel} · ${dueLabel}`;
                }

                const cardForm = trigger.querySelector('[data-task-status-form]');
                if (cardForm) {
                    const hiddenInput = cardForm.querySelector('input[name="status"]');
                    const submitButton = cardForm.querySelector('[data-task-status-button]');
                    if (hiddenInput) hiddenInput.value = nextStatus;
                    if (submitButton) submitButton.textContent = nextLabel;
                }

                if (activeTaskCard === trigger) {
                    badge.textContent = completed ? 'Completed' : 'Open';
                    statusInput.value = nextStatus;
                    statusLabel.textContent = nextLabel;
                }
            };

            const submitTaskStatus = async (form, trigger) => {
                const action = form.getAttribute('action');
                if (!action) return;

                const formData = new FormData(form);

                try {
                    const response = await fetch(action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData,
                        credentials: 'same-origin',
                    });

                    const payload = await response.json();

                    if (!response.ok || !payload.ok) {
                        throw new Error(payload.message || 'Task update failed.');
                    }

                    applyTaskState(trigger, !!payload.completed);
                } catch (error) {
                    console.error(error);
                    window.alert('We could not update this task in place yet. Please try again.');
                }
            };

            document.querySelectorAll('[data-open-task]').forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    activeTaskCard = trigger;
                    title.textContent = trigger.getAttribute('data-drawer-title') || 'Task';
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
                        statusLabel.textContent = 'Update task';
                    }
                    let parsed = [];
                    try { parsed = JSON.parse(trigger.getAttribute('data-drawer-comments') || '[]'); } catch (error) {}
                    comments.innerHTML = '';
                    parsed.forEach((comment) => {
                        const node = document.createElement('div');
                        node.className = 'task-comment';
                        node.innerHTML = `<div class="task-drawer-avatar">${(comment.author || 'U').slice(0,1).toUpperCase()}</div><div><div style="font-weight:600;margin-bottom:4px;">${comment.author || 'Founder'}</div><div>${comment.message || ''}</div></div>`;
                        comments.appendChild(node);
                    });
                    drawer.classList.add('open');
                });
            });

            document.querySelectorAll('[data-task-status-form]').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    const trigger = form.closest('[data-open-task]');
                    await submitTaskStatus(form, trigger);
                });
            });

            statusForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const trigger = activeTaskCard;
                await submitTaskStatus(statusForm, trigger);
            });

            drawer.querySelector('[data-close-task]')?.addEventListener('click', () => {
                drawer.classList.remove('open');
                activeTaskCard = null;
            });
        })();
    </script>
@endsection
