@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'prototype-dashboard-page')

@php
    $workspace = $dashboard['workspace'] ?? [];
    $founder = $dashboard['founder'] ?? auth()->user();
    $company = $dashboard['company'] ?? null;
    $taskEntries = $workspace['task_center_entries'] ?? [];
    $projectName = trim((string) ($company?->company_name ?? 'New Project'));
    $hasProject = !empty($taskEntries) || strcasecmp($projectName, 'New Project') !== 0;
    $osEmbedMode = request()->boolean('os_embed');
@endphp

@section('head')
    <style>
        .tasks-hello { margin:0 0 8px; font-size:44px; line-height:1; letter-spacing:-0.04em; font-weight:650; }
        .tasks-sub { margin:0 0 20px; font-size:14px; color:var(--text-muted); }
        .tasks-section-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.08em; color:var(--text-muted); margin-bottom:12px; }
        .task-card { background:#fff; border:0.5px solid var(--border); border-radius:16px; padding:16px 18px; box-shadow:var(--shadow-sm); margin-bottom:12px; }
        .task-meta { display:flex; align-items:center; gap:8px; margin-bottom:10px; font-size:11px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:var(--text-muted); }
        .task-body { display:flex; align-items:flex-start; justify-content:space-between; gap:18px; }
        .task-title { margin:0 0 4px; font-size:18px; font-weight:600; color:var(--text); letter-spacing:-0.01em; }
        .task-desc { margin:0; font-size:13px; color:var(--text-muted); line-height:1.5; max-width:520px; }
        .task-cta { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; border:0; background:#111110; color:#fff; font:inherit; font-size:13px; font-weight:600; white-space:nowrap; cursor:pointer; text-decoration:none; }
        .task-cta-spark { font-size:12px; }
        .task-title-done { display:flex; align-items:center; gap:10px; }
        .task-check { display:inline-flex; width:20px; height:20px; border-radius:50%; background:#111110; color:#fff; align-items:center; justify-content:center; font-size:12px; }
        .task-strike { text-decoration:line-through; opacity:0.7; }
        .empty-card { max-width:560px; margin:60px auto 0; background:var(--surface); border:0.5px solid var(--border); border-radius:14px; padding:28px 28px 24px; box-shadow:var(--shadow-md); text-align:left; }
        .empty-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.08em; color:var(--text-muted); margin-bottom:8px; }
        .empty-card h2 { margin:0 0 10px; font-size:18px; font-weight:600; color:var(--text); }
        .empty-card p { margin:0 0 18px; font-size:13px; color:var(--text-muted); line-height:1.55; }
        .tasks-stage {
            width:100%;
            max-width:1240px;
            margin:0 auto;
        }
        .tasks-window {
            width:min(920px, calc(100% - 80px));
            margin:36px auto 0;
            background:var(--surface);
            border:0.5px solid var(--border);
            border-radius:18px;
            box-shadow:0 8px 24px rgba(30,24,16,0.08), 0 1px 2px rgba(30,24,16,0.06);
            overflow:hidden;
        }
        .tasks-window-header {
            display:flex;
            align-items:center;
            justify-content:center;
            position:relative;
            padding:14px 20px;
            border-bottom:0.5px solid var(--hairline);
            background:var(--surface);
        }
        .tasks-window-title {
            font-size:11px;
            font-weight:600;
            letter-spacing:.10em;
            text-transform:uppercase;
            color:var(--text-muted);
        }
        .tasks-window-body { padding:28px 28px 30px; }
        .tasks-embed {
            padding: 24px;
            background: #fff;
            min-height: 100%;
        }
        @media (max-width: 980px) { .tasks-hello { font-size:36px; } .task-body { flex-direction:column; } }
    </style>
@endsection

@section('content')
    @if ($osEmbedMode)
        <div class="tasks-embed">
            @if (!$hasProject)
                <div class="empty-card">
                    <div class="empty-label">Tasks</div>
                    <h2>No tasks yet</h2>
                    <p>For us to generate a launch plan tailored to your business, you have to tell us more about your project.</p>
                    <a class="new-project-btn" href="{{ route('dashboard') }}">
                        <span class="plus">+</span>
                        <span>New Project</span>
                    </a>
                </div>
            @else
                <div class="tasks-stage">
                    <h1 class="tasks-hello">Welcome back {{ strtok((string) ($founder->full_name ?? 'Founder'), ' ') }},</h1>
                    <p class="tasks-sub">Here's what's on for you for this week:</p>
                    <div class="tasks-section-label">Tasks</div>

                    @forelse($taskEntries as $task)
                        <article class="task-card">
                            <div class="task-meta">
                                <span class="task-meta-label">{{ strtoupper($task['label'] ?? 'Milestone') }}</span>
                                @if(!empty($task['due']))
                                    <span class="task-meta-dot">·</span>
                                    <span class="task-meta-due">{{ strtoupper($task['due']) }}</span>
                                @endif
                            </div>
                            <div class="task-body">
                                <div>
                                    @if(!empty($task['completed']))
                                        <h3 class="task-title task-title-done">
                                            <span class="task-check">✓</span>
                                            <span class="task-strike">{{ $task['title'] ?? 'Task' }}</span>
                                        </h3>
                                    @else
                                        <h3 class="task-title">{{ $task['title'] ?? 'Task' }}</h3>
                                    @endif
                                    <p class="task-desc">{{ $task['description'] ?? 'Continue this task from your founder workspace.' }}</p>
                                </div>
                                @if(empty($task['completed']))
                                    <a class="task-cta" href="{{ route('dashboard') }}">
                                        <span class="task-cta-spark">✦</span>
                                        <span>{{ !empty($task['mentor_name']) ? 'Continue with AI' : 'Open in OS' }}</span>
                                    </a>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="empty-state">
                            <h2>No tasks yet</h2>
                            <p>Start a new project and we’ll generate your launch plan here.</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    @else
        <x-os.prototype-shell :founder="$founder" :workspace="$workspace" active-tile="tasks">
            <div class="workspace">
                <div class="tasks-window" role="dialog" aria-label="Tasks">
                    <div class="tasks-window-header">
                        <span class="traffic">
                            <span class="red"></span>
                            <span class="yellow"></span>
                            <span class="green"></span>
                        </span>
                        <span class="tasks-window-title">TASKS</span>
                    </div>

                    <div class="tasks-window-body">
                        @if (!$hasProject)
                            <div class="empty-card">
                                <div class="empty-label">Tasks</div>
                                <h2>No tasks yet</h2>
                                <p>For us to generate a launch plan tailored to your business, you have to tell us more about your project.</p>
                                <a class="new-project-btn" href="{{ route('dashboard') }}">
                                    <span class="plus">+</span>
                                    <span>New Project</span>
                                </a>
                            </div>
                        @else
                            <div class="tasks-stage">
                                <h1 class="tasks-hello">Welcome back {{ strtok((string) ($founder->full_name ?? 'Founder'), ' ') }},</h1>
                                <p class="tasks-sub">Here's what's on for you for this week:</p>
                                <div class="tasks-section-label">Tasks</div>

                                @forelse($taskEntries as $task)
                                    <article class="task-card">
                                        <div class="task-meta">
                                            <span class="task-meta-label">{{ strtoupper($task['label'] ?? 'Milestone') }}</span>
                                            @if(!empty($task['due']))
                                                <span class="task-meta-dot">·</span>
                                                <span class="task-meta-due">{{ strtoupper($task['due']) }}</span>
                                            @endif
                                        </div>
                                        <div class="task-body">
                                            <div>
                                                @if(!empty($task['completed']))
                                                    <h3 class="task-title task-title-done">
                                                        <span class="task-check">✓</span>
                                                        <span class="task-strike">{{ $task['title'] ?? 'Task' }}</span>
                                                    </h3>
                                                @else
                                                    <h3 class="task-title">{{ $task['title'] ?? 'Task' }}</h3>
                                                @endif
                                                <p class="task-desc">{{ $task['description'] ?? 'Continue this task from your founder workspace.' }}</p>
                                            </div>
                                            @if(empty($task['completed']))
                                                <a class="task-cta" href="{{ route('dashboard') }}">
                                                    <span class="task-cta-spark">✦</span>
                                                    <span>{{ !empty($task['mentor_name']) ? 'Continue with AI' : 'Open in OS' }}</span>
                                                </a>
                                            @endif
                                        </div>
                                    </article>
                                @empty
                                    <div class="empty-state">
                                        <h2>No tasks yet</h2>
                                        <p>Start a new project and we’ll generate your launch plan here.</p>
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-os.prototype-shell>
    @endif
@endsection
