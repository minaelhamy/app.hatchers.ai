@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page {
            padding: 0;
        }

        .founder-home {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 220px minmax(0, 1fr) 220px;
            background: #f8f5ee;
        }

        .founder-sidebar,
        .founder-rightbar {
            background: rgba(255, 252, 247, 0.8);
            border-color: var(--line);
            border-style: solid;
            border-width: 0 1px 0 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .founder-rightbar {
            border-width: 0 0 0 1px;
            background: rgba(255, 251, 246, 0.9);
        }

        .founder-sidebar-inner,
        .founder-rightbar-inner {
            padding: 22px 18px;
        }

        .founder-brand {
            display: inline-block;
            margin-bottom: 24px;
        }

        .founder-brand img {
            width: 168px;
            height: auto;
            display: block;
        }

        .founder-nav {
            display: grid;
            gap: 6px;
        }

        .founder-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 14px;
            text-decoration: none;
            color: var(--ink);
            font-size: 0.98rem;
        }

        .founder-nav-item.active {
            background: #ece6db;
        }

        .founder-nav-icon {
            width: 18px;
            text-align: center;
            color: var(--muted);
        }

        .founder-sidebar-footer {
            margin-top: auto;
            padding: 18px;
            border-top: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .founder-user {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .founder-avatar {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            background: #b0a999;
            color: #fff;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 0.92rem;
            flex-shrink: 0;
        }

        .founder-user-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .founder-main {
            padding: 26px 28px 24px;
        }

        .founder-main-inner {
            max-width: 700px;
            margin: 0 auto;
        }

        .founder-welcome h1 {
            font-size: clamp(2rem, 3vw, 3.1rem);
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .founder-welcome p {
            color: var(--muted);
            font-size: 1.02rem;
            margin-bottom: 28px;
        }

        .founder-section {
            margin-bottom: 20px;
        }

        .founder-section h2 {
            font-size: 1.15rem;
            margin-bottom: 12px;
        }

        .founder-block {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(220, 207, 191, 0.65);
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 10px 28px rgba(52, 41, 26, 0.04);
        }

        .founder-block + .founder-block {
            margin-top: 10px;
        }

        .founder-alert {
            border-radius: 14px;
            padding: 14px 16px;
            border: 1px solid rgba(220, 207, 191, 0.65);
            background: rgba(255, 255, 255, 0.92);
        }

        .founder-alert + .founder-alert {
            margin-top: 10px;
        }

        .founder-alert.warning {
            border-color: rgba(154, 107, 27, 0.22);
            background: rgba(255, 248, 238, 0.96);
        }

        .founder-alert.danger {
            border-color: rgba(179, 34, 83, 0.20);
            background: rgba(255, 242, 246, 0.96);
        }

        .founder-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .founder-meta {
            font-size: 0.84rem;
            color: #2fac5a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }

        .founder-subtle {
            color: var(--muted);
            font-size: 0.98rem;
        }

        .founder-badge {
            padding: 8px 14px;
            border-radius: 10px;
            background: #f0ece4;
            color: #7a7267;
            font-size: 0.95rem;
            white-space: nowrap;
        }

        .founder-badge.success {
            background: linear-gradient(135deg, #2fba5a, #93e0a5);
            color: white;
        }

        .founder-badge.info {
            background: linear-gradient(135deg, #4c91ec, #68b4ff);
            color: white;
        }

        .founder-task-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 8px;
        }

        .founder-task-label {
            font-size: 0.83rem;
            color: var(--rose);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .founder-task-cta {
            border: 0;
            border-radius: 10px;
            padding: 10px 16px;
            background: linear-gradient(90deg, #8e1c74, #ff2c35);
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        .founder-task-card.completed .founder-task-title {
            text-decoration: line-through;
            opacity: 0.75;
        }

        .founder-task-card {
            cursor: pointer;
        }

        .founder-learning-card,
        .founder-mentor-card {
            cursor: pointer;
        }

        .founder-assistant-bar {
            margin-top: 26px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            border: 1px solid rgba(220, 207, 191, 0.8);
            border-radius: 16px;
            padding: 14px 16px;
            box-shadow: 0 12px 24px rgba(52, 41, 26, 0.05);
        }

        .founder-assistant-icon,
        .founder-assistant-send {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 1px solid rgba(220, 207, 191, 0.8);
            display: grid;
            place-items: center;
            background: #fffdf9;
            color: var(--ink);
            flex-shrink: 0;
        }

        .founder-assistant-input {
            flex: 1;
            color: #8a8378;
        }

        .founder-rightbar h3 {
            font-size: 0.83rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 12px;
        }

        .notification-badge {
            width: 20px;
            height: 20px;
            border-radius: 999px;
            background: var(--rose);
            color: white;
            font-size: 0.76rem;
            display: grid;
            place-items: center;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            font-size: 0.84rem;
            color: var(--muted);
        }

        .calendar-head {
            text-align: center;
            font-size: 0.74rem;
            text-transform: uppercase;
        }

        .calendar-day {
            width: 28px;
            height: 28px;
            margin: 0 auto;
            border-radius: 999px;
            display: grid;
            place-items: center;
            color: var(--ink);
        }

        .calendar-day.dim {
            color: #b9b1a5;
        }

        .calendar-day.today {
            background: #6d675f;
            color: white;
        }

        .notification-list,
        .tool-list {
            display: grid;
            gap: 10px;
            margin-top: 14px;
        }

        .notification-item,
        .tool-item {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(220, 207, 191, 0.65);
            border-radius: 14px;
            padding: 12px 14px;
        }

        .notification-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .notification-icon {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: linear-gradient(135deg, #8e1c74, #ff2c35);
            color: white;
            display: grid;
            place-items: center;
            font-size: 0.75rem;
            flex-shrink: 0;
        }

        .tool-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }

        .tool-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            border: 1px solid rgba(222, 60, 109, 0.24);
            color: #e02961;
            display: grid;
            place-items: center;
            font-size: 0.82rem;
        }

        .founder-drawer {
            position: fixed;
            top: 0;
            right: 0;
            width: min(480px, 100%);
            height: 100vh;
            background: #fffdf8;
            border-left: 1px solid rgba(220, 207, 191, 0.8);
            box-shadow: -10px 0 30px rgba(52, 41, 26, 0.08);
            transform: translateX(100%);
            transition: transform 0.25s ease;
            z-index: 40;
            display: flex;
            flex-direction: column;
        }

        .founder-drawer.open {
            transform: translateX(0);
        }

        .founder-drawer-header {
            padding: 20px 24px 12px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
        }

        .founder-drawer-body {
            padding: 0 24px 24px;
            overflow-y: auto;
        }

        .founder-drawer-close {
            border: 0;
            background: transparent;
            font-size: 1.6rem;
            color: var(--muted);
            cursor: pointer;
            line-height: 1;
        }

        .drawer-eyebrow {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #4c91ec;
            margin-bottom: 10px;
        }

        .drawer-eyebrow.task {
            color: var(--rose);
        }

        .drawer-grid {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 14px 10px;
            margin: 24px 0;
        }

        .drawer-label {
            color: var(--muted);
        }

        .drawer-comments {
            margin-top: 18px;
        }

        .drawer-comment {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            background: white;
            border: 1px solid rgba(220, 207, 191, 0.65);
            border-radius: 14px;
            padding: 14px;
            margin-top: 12px;
        }

        .drawer-comment-box {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
            border: 1px solid rgba(220, 207, 191, 0.8);
            border-radius: 14px;
            padding: 12px 14px;
            color: #8a8378;
            background: #f4efe6;
        }

        @media (max-width: 1240px) {
            .founder-home {
                grid-template-columns: 220px 1fr;
            }

            .founder-rightbar {
                display: none;
            }
        }

        @media (max-width: 900px) {
            .founder-home {
                grid-template-columns: 1fr;
            }

            .founder-sidebar {
                min-height: auto;
                border-right: 0;
                border-bottom: 1px solid var(--line);
            }

            .founder-sidebar-footer {
                display: none;
            }

            .founder-main {
                padding: 20px 16px 24px;
            }

            .founder-main-inner {
                max-width: none;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $workspace = $dashboard['workspace'];
        $mentor = $workspace['mentor_session'];
        $learning = $workspace['learning_item'];
        $tasks = $workspace['task_cards'];
        $notifications = $workspace['notifications'];
        $unreadNotificationCount = $workspace['unread_notification_count'];
        $calendar = $workspace['calendar'];
        $aiTools = $workspace['ai_tools'];
        $syncStatus = $dashboard['sync_status'];
        $commerceAlerts = $dashboard['commerce_alerts'] ?? [];
        $commerceOperations = $dashboard['commerce_operations'] ?? [];
        $automationSummary = $dashboard['automation_summary'] ?? ['active_count' => 0, 'items' => []];
        $revenueOs = $dashboard['revenue_os'] ?? ['metrics' => [], 'daily_plan' => ['tasks' => []], 'best_channel' => null];
        $nextBestActions = $workspace['next_best_actions'];
    @endphp

    <div class="founder-home">
        <aside class="founder-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $company->business_model ?? 'hybrid',
                'activeKey' => 'home',
                'navClass' => 'founder-nav',
                'itemClass' => 'founder-nav-item',
                'iconClass' => 'founder-nav-icon',
                'innerClass' => 'founder-sidebar-inner',
                'brandClass' => 'founder-brand',
                'footerClass' => 'founder-sidebar-footer',
                'userClass' => 'founder-user',
                'avatarClass' => 'founder-avatar',
            ])
        </aside>

        <main class="founder-main">
            <div class="founder-main-inner">
                <section class="founder-welcome">
                    <h1>Welcome back {{ $workspace['first_name'] }},</h1>
                    <p>Here’s what’s on for you for this week:</p>
                </section>

                <section class="founder-section">
                    <h2>{{ $mentor['section_label'] }}</h2>
                    <article class="founder-block founder-mentor-card"
                        data-open-drawer="task"
                        data-drawer-title="{{ e($mentor['title']) }}"
                        data-drawer-due="{{ e($mentor['date_label']) }}"
                        data-drawer-owner="{{ e($mentor['subtitle']) }}"
                        data-drawer-description="{{ e($mentor['drawer_description']) }}"
                        data-drawer-badge="{{ e($mentor['badge']) }}"
                        data-drawer-comments='@json($notifications)'>
                        <div class="founder-row">
                            <div>
                                <div class="founder-meta">{{ $mentor['date_label'] }}</div>
                                <div style="font-size: 1.1rem; font-weight: 600;">{{ $mentor['title'] }}</div>
                                <div class="founder-subtle" style="margin-top: 4px;">{{ $mentor['subtitle'] }}</div>
                            </div>
                            <div class="founder-badge {{ $mentor['badge_tone'] === 'success' ? 'success' : '' }}">{{ $mentor['badge'] }}</div>
                        </div>
                    </article>
                </section>

                <section class="founder-section">
                    <h2>Learning</h2>
                    <article class="founder-block founder-learning-card"
                        data-open-drawer="lesson"
                        data-drawer-title="{{ e($learning['detail_heading']) }}"
                        data-drawer-due="{{ e($learning['detail_due']) }}"
                        data-drawer-owner="{{ e($learning['detail_owner']) }}"
                        data-drawer-description="{{ e($learning['detail_description']) }}"
                        data-drawer-badge="{{ e($learning['badge']) }}"
                        data-drawer-comments='@json($learning["comments"])'>
                        <div class="founder-row">
                            <div>
                                <div class="founder-meta" style="color: #4c91ec;">THURSDAY, DEC 25 · 1:00PM</div>
                                <div style="font-size: 1.1rem; font-weight: 600;">{{ $learning['title'] }}</div>
                                <div class="founder-subtle" style="margin-top: 4px;">{{ $learning['subtitle'] }}</div>
                            </div>
                            <div class="founder-badge">{{ $learning['badge'] }}</div>
                        </div>
                    </article>
                </section>

                <section class="founder-section">
                    <h2>Start Here Today</h2>
                    @foreach (($workspace['guided_path'] ?? []) as $step)
                        <article class="founder-block">
                            <div class="founder-row" style="align-items:flex-start;">
                                <div>
                                    <div style="font-size: 1.04rem; font-weight: 600;">{{ $step['title'] }}</div>
                                    <div class="founder-subtle" style="margin-top: 4px;">{{ $step['description'] }}</div>
                                </div>
                                <a class="founder-badge info" href="{{ $step['href'] }}" style="text-decoration:none;">{{ $step['label'] }}</a>
                            </div>
                        </article>
                    @endforeach
                </section>

                <section class="founder-section">
                    <h2>Next Best Actions</h2>
                    @foreach ($nextBestActions as $action)
                        <article class="founder-block">
                            <div class="founder-row" style="align-items:flex-start;">
                                <div>
                                    <div style="font-size: 1.04rem; font-weight: 600;">{{ $action['title'] }}</div>
                                    <div class="founder-subtle" style="margin-top: 4px;">{{ $action['description'] }}</div>
                                </div>
                                <a class="founder-badge" href="{{ $action['href'] }}" style="text-decoration:none;">{{ $action['label'] }}</a>
                            </div>
                        </article>
                    @endforeach
                </section>

                @if (!empty($workspace['daily_revenue_plan']['tasks']))
                    <section class="founder-section">
                        <h2>Daily Revenue Plan</h2>
                        @foreach ($workspace['daily_revenue_plan']['tasks'] as $task)
                            <article class="founder-block">
                                <div class="founder-row" style="align-items:flex-start;">
                                    <div>
                                        <div style="font-size: 1.04rem; font-weight: 600;">{{ $task['title'] }}</div>
                                        <div class="founder-subtle" style="margin-top: 4px;">{{ $task['description'] }}</div>
                                    </div>
                                    <a class="founder-badge" href="{{ $task['href'] }}" style="text-decoration:none;">{{ $task['label'] }}</a>
                                </div>
                            </article>
                        @endforeach
                    </section>
                @endif

                <section class="founder-section">
                    <h2>First 100 Progress</h2>
                    <article class="founder-block">
                        <div class="founder-row" style="align-items:flex-start;">
                            <div>
                                <div style="font-size: 1.04rem; font-weight: 600;">{{ $revenueOs['metrics']['customers_won'] ?? 0 }} / 100 customers won</div>
                                <div class="founder-subtle" style="margin-top: 4px;">
                                    {{ $revenueOs['metrics']['identified_leads'] ?? 0 }} leads tracked ·
                                    {{ $revenueOs['metrics']['active_conversations'] ?? 0 }} active conversations ·
                                    {{ $revenueOs['metrics']['follow_up_due'] ?? 0 }} follow-up due
                                </div>
                            </div>
                            <a class="founder-badge" href="{{ route('founder.first-100') }}" style="text-decoration:none;">Open First 100</a>
                        </div>
                    </article>
                </section>

                <section class="founder-section">
                    <h2>Tasks</h2>
                    @foreach ($tasks as $task)
                        <article class="founder-block founder-task-card {{ $task['completed'] ? 'completed' : '' }}"
                            data-open-drawer="task"
                            data-drawer-title="{{ e($task['detail_heading']) }}"
                            data-drawer-due="{{ e($task['detail_due']) }}"
                            data-drawer-owner="{{ e($task['detail_owner']) }}"
                            data-drawer-description="{{ e($task['detail_description']) }}"
                            data-drawer-badge="{{ e($task['cta'] ?: 'View task') }}"
                            data-drawer-comments='@json($task["comments"])'>
                            <div class="founder-task-top">
                                <div class="founder-task-label">{{ strtoupper($task['label']) }} · {{ strtoupper($task['due']) }}</div>
                                @if (!$task['completed'] && $task['cta'] !== '')
                                    <button class="founder-task-cta" type="button">{{ $task['cta'] }}</button>
                                @endif
                            </div>
                            <div class="founder-task-title" style="font-size: 1.08rem; font-weight: 600;">{{ $task['title'] }}</div>
                            <div class="founder-subtle" style="margin-top: 4px;">{{ $task['description'] }}</div>
                        </article>
                    @endforeach
                </section>

                @if (!empty($commerceOperations['queue']))
                    <section class="founder-section">
                        <h2>Operational Queue</h2>
                        @foreach ($commerceOperations['queue'] as $item)
                            <article class="founder-block">
                                <div class="founder-row" style="align-items:flex-start;">
                                    <div>
                                        <div style="font-size:1.02rem;font-weight:600;">{{ $item['title'] }}</div>
                                        <div class="founder-subtle" style="margin-top:4px;">{{ $item['description'] }}</div>
                                    </div>
                                    <a class="founder-badge" href="{{ $item['href'] }}" style="text-decoration:none;">{{ $item['label'] }}</a>
                                </div>
                            </article>
                        @endforeach
                    </section>
                @endif

                @if (!empty($automationSummary['items']))
                    <section class="founder-section">
                        <h2>Active Reminder Rules</h2>
                        @foreach ($automationSummary['items'] as $rule)
                            <article class="founder-block">
                                <div class="founder-row" style="align-items:flex-start;">
                                    <div>
                                        <div style="font-size:1.02rem;font-weight:600;">{{ $rule['name'] }}</div>
                                        <div class="founder-subtle" style="margin-top:4px;">{{ ucfirst($rule['module_scope']) }} · {{ ucfirst($rule['delivery']) }} · {{ $rule['status_label'] }}</div>
                                    </div>
                                    <a class="founder-badge" href="{{ $rule['href'] }}" style="text-decoration:none;">{{ $rule['cta_label'] }}</a>
                                </div>
                            </article>
                        @endforeach
                    </section>
                @endif

                @if (!empty($commerceAlerts))
                    <section class="founder-section">
                        <h2>Commerce Alerts</h2>
                        @foreach ($commerceAlerts as $alert)
                            <article class="founder-alert {{ $alert['type'] ?? 'warning' }}">
                                <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start;">
                                    <div>
                                        <div style="font-size:1.02rem;font-weight:600;">{{ $alert['title'] }}</div>
                                        <div class="founder-subtle" style="margin-top:4px;">{{ $alert['description'] }}</div>
                                    </div>
                                    <a class="founder-badge" href="{{ $alert['href'] }}" style="text-decoration:none;">{{ $alert['label'] }}</a>
                                </div>
                            </article>
                        @endforeach
                    </section>
                @endif

                <div id="assistant-bar" class="founder-assistant-bar">
                    <div class="founder-assistant-icon">◔</div>
                    <div class="founder-assistant-input">{{ $workspace['quick_prompt'] ?: 'Ask AI anything about your project...' }}</div>
                    <div class="founder-assistant-send">↵</div>
                </div>
            </div>
        </main>

        <aside class="founder-rightbar">
            <div class="founder-rightbar-inner">
                <div class="founder-row" style="margin-bottom: 18px;">
                    <a href="{{ route('founder.inbox') }}" style="text-decoration: none;"><h3 style="margin: 0;">Notifications</h3></a>
                    <a href="{{ route('founder.inbox') }}" class="notification-badge" style="text-decoration: none;">{{ $unreadNotificationCount }}</a>
                </div>

                <div style="margin-bottom: 14px; color: var(--muted); font-size: 0.86rem;">{{ $calendar['month_label'] }}</div>
                <div class="calendar-grid" style="margin-bottom: 10px;">
                    @foreach (['S','M','T','W','T','F','S'] as $dayLabel)
                        <div class="calendar-head">{{ $dayLabel }}</div>
                    @endforeach
                    @foreach ($calendar['days'] as $day)
                        <div class="calendar-day {{ !$day['in_month'] ? 'dim' : '' }} {{ $day['is_today'] ? 'today' : '' }}">{{ $day['day'] }}</div>
                    @endforeach
                </div>

                <div class="notification-list">
                    @forelse ($notifications as $notification)
                        <div class="notification-item">
                            <div class="notification-icon">{{ strtoupper(substr($notification['kind'], 0, 1)) }}</div>
                            <div>
                                <div style="font-weight: 600;">{{ $notification['title'] }}</div>
                                <div class="founder-subtle" style="margin-top: 4px;">{{ $notification['meta'] }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="notification-item">
                            <div class="notification-icon">i</div>
                            <div>
                                <div style="font-weight: 600;">No notifications yet</div>
                                <div class="founder-subtle" style="margin-top: 4px;">New founder updates will appear here.</div>
                            </div>
                        </div>
                    @endforelse
                </div>

                <h3 style="margin-top: 22px;"><a href="{{ route('founder.activity') }}" style="text-decoration:none;color:inherit;">Activity Center</a></h3>
                <div class="tool-list">
                    @foreach (array_slice($dashboard['activity_feed'], 0, 3) as $item)
                        <div class="tool-item" style="display:block;">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                                <div>{{ $item['module'] }}</div>
                                <span class="founder-subtle">{{ $item['updated_at'] ?: 'Recently' }}</span>
                            </div>
                            <div class="founder-subtle" style="margin-top:4px;">{{ $item['message'] }}</div>
                        </div>
                    @endforeach
                </div>

                <h3 style="margin-top: 22px;"><a href="{{ route('founder.first-100') }}" style="text-decoration:none;color:inherit;">First 100</a></h3>
                <div class="tool-list">
                    <div class="tool-item" style="display:block;">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                            <div>Customers won</div>
                            <span class="notification-badge">{{ $revenueOs['metrics']['customers_won'] ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="tool-item" style="display:block;">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                            <div>Follow-up due</div>
                            <span class="notification-badge" style="background:rgba(154,107,27,0.12);color:#9a6b1b;">{{ $revenueOs['metrics']['follow_up_due'] ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="tool-item" style="display:block;">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                            <div>Best channel</div>
                            <span class="founder-subtle">{{ $revenueOs['best_channel']['channel_label'] ?? 'No signal yet' }}</span>
                        </div>
                    </div>
                    <div class="tool-item" style="display:block;">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                            <div>Priority channel today</div>
                            <span class="founder-subtle">{{ $revenueOs['acquisition_engine']['priority_channel'] ?? 'No plan yet' }}</span>
                        </div>
                    </div>
                </div>

                <h3 style="margin-top: 22px;">Module Sync</h3>
                <div class="tool-list">
                    @foreach ($syncStatus['modules'] as $status)
                        <div class="tool-item" style="display:block;">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                                <div>{{ $status['module'] }}</div>
                                <span class="notification-badge" style="
                                    background:
                                    @if ($status['tone'] === 'success') rgba(44,122,87,0.12)
                                    @elseif ($status['tone'] === 'warning') rgba(154,107,27,0.12)
                                    @else rgba(179,34,83,0.10)
                                    @endif
                                    ; color:
                                    @if ($status['tone'] === 'success') #21643a
                                    @elseif ($status['tone'] === 'warning') #9a6b1b
                                    @else #b32253
                                    @endif
                                    ;">
                                    {{ $status['status'] }}
                                </span>
                            </div>
                            <div class="founder-subtle" style="margin-top:4px;">{{ $status['reason'] }}</div>
                            <div class="founder-subtle" style="margin-top:4px;">{{ $status['updated_at'] ?: 'Not synced yet' }}</div>
                        </div>
                    @endforeach
                </div>

                @if (!empty($commerceOperations))
                    <h3 style="margin-top: 22px;">Commerce Ops</h3>
                    <div class="tool-list">
                        <div class="tool-item" style="display:block;">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                                <div>Pending orders</div>
                                <span class="notification-badge" style="background:rgba(154,107,27,0.12);color:#9a6b1b;">{{ $commerceOperations['pending_orders'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="tool-item" style="display:block;">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                                <div>Unpaid orders</div>
                                <span class="notification-badge" style="background:rgba(179,34,83,0.10);color:#b32253;">{{ $commerceOperations['unpaid_orders'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="tool-item" style="display:block;">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                                <div>Ready to ship</div>
                                <span class="notification-badge" style="background:rgba(44,122,87,0.12);color:#21643a;">{{ $commerceOperations['ready_to_ship_orders'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="tool-item" style="display:block;">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                                <div>Pending bookings</div>
                                <span class="notification-badge" style="background:rgba(154,107,27,0.12);color:#9a6b1b;">{{ $commerceOperations['pending_bookings'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="tool-item" style="display:block;">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                                <div>Unscheduled bookings</div>
                                <span class="notification-badge" style="background:rgba(179,34,83,0.10);color:#b32253;">{{ $commerceOperations['unscheduled_bookings'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="tool-item" style="display:block;">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                                <div>Need staff assignment</div>
                                <span class="notification-badge" style="background:rgba(154,107,27,0.12);color:#9a6b1b;">{{ $commerceOperations['needs_staff_assignment'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <h3 style="margin-top: 22px;">AI Tools</h3>
                <div class="tool-list">
                    @foreach ($aiTools as $tool)
                        <div class="tool-item">
                            <div class="tool-icon">□</div>
                            <div>{{ $tool['title'] }}</div>
                        </div>
                    @endforeach
                </div>

                @if (!empty($automationSummary['active_count']))
                    <h3 style="margin-top: 22px;"><a href="{{ route('founder.automations') }}" style="text-decoration:none;color:inherit;">Reminder Rules</a></h3>
                    <div class="tool-list">
                        @foreach (array_slice($automationSummary['items'], 0, 3) as $rule)
                            <a class="tool-item" href="{{ $rule['href'] }}" style="display:block;text-decoration:none;color:inherit;">
                                <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                                    <div>{{ $rule['name'] }}</div>
                                    <span class="founder-subtle">{{ $rule['delivery'] }}</span>
                                </div>
                                <div class="founder-subtle" style="margin-top:4px;">{{ $rule['status_label'] }}</div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </aside>
    </div>

    <aside class="founder-drawer" data-founder-drawer>
        <div class="founder-drawer-header">
            <div>
                <div class="drawer-eyebrow" data-drawer-eyebrow>Lesson</div>
                <h2 data-drawer-title style="font-size: 1.7rem; margin: 0;">Item title</h2>
            </div>
            <button class="founder-drawer-close" type="button" data-close-drawer>&times;</button>
        </div>
        <div class="founder-drawer-body">
            <div class="founder-badge" data-drawer-badge>Open</div>
            <div class="drawer-grid">
                <div class="drawer-label">Due date</div>
                <div data-drawer-due></div>
                <div class="drawer-label">Owner</div>
                <div data-drawer-owner></div>
                <div class="drawer-label">Description</div>
                <div data-drawer-description></div>
            </div>

            <div class="drawer-comments">
                <div class="drawer-label" style="margin-bottom: 10px;">Comments</div>
                <div data-drawer-comments></div>
                <div class="drawer-comment-box">
                    <div class="founder-avatar">{{ strtoupper(substr($founder->full_name, 0, 1)) }}</div>
                    <div style="flex: 1;">Add comment...</div>
                    <div>🔗</div>
                    <div>↑</div>
                </div>
            </div>
        </div>
    </aside>
@endsection

@section('scripts')
    <script>
        (() => {
            const drawer = document.querySelector('[data-founder-drawer]');
            if (!drawer) return;

            const closeButton = drawer.querySelector('[data-close-drawer]');
            const title = drawer.querySelector('[data-drawer-title]');
            const eyebrow = drawer.querySelector('[data-drawer-eyebrow]');
            const due = drawer.querySelector('[data-drawer-due]');
            const owner = drawer.querySelector('[data-drawer-owner]');
            const description = drawer.querySelector('[data-drawer-description]');
            const badge = drawer.querySelector('[data-drawer-badge]');
            const comments = drawer.querySelector('[data-drawer-comments]');

            const openDrawer = (trigger) => {
                const type = trigger.getAttribute('data-open-drawer') || 'lesson';
                title.textContent = trigger.getAttribute('data-drawer-title') || 'Details';
                eyebrow.textContent = type === 'task' ? 'Task' : 'Lesson';
                eyebrow.classList.toggle('task', type === 'task');
                due.textContent = trigger.getAttribute('data-drawer-due') || 'This week';
                owner.textContent = trigger.getAttribute('data-drawer-owner') || 'Hatchers Ai OS';
                description.textContent = trigger.getAttribute('data-drawer-description') || 'No description yet.';
                badge.textContent = trigger.getAttribute('data-drawer-badge') || 'Open';

                const rawComments = trigger.getAttribute('data-drawer-comments') || '[]';
                let parsedComments = [];
                try {
                    parsedComments = JSON.parse(rawComments);
                } catch (error) {
                    parsedComments = [];
                }

                comments.innerHTML = '';
                parsedComments.forEach((comment) => {
                    const node = document.createElement('div');
                    node.className = 'drawer-comment';
                    node.innerHTML = `
                        <div class="founder-avatar">${(comment.author || 'U').slice(0, 1).toUpperCase()}</div>
                        <div>
                            <div style="font-weight: 600; margin-bottom: 4px;">${comment.author || 'Founder'}</div>
                            <div>${comment.message || ''}</div>
                        </div>
                    `;
                    comments.appendChild(node);
                });

                drawer.classList.add('open');
            };

            document.querySelectorAll('[data-open-drawer]').forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    if (event.target.closest('button') && !event.target.matches('[data-open-drawer]')) {
                        event.preventDefault();
                    }
                    event.preventDefault();
                    openDrawer(trigger);
                });
            });

            closeButton?.addEventListener('click', () => drawer.classList.remove('open'));
        })();
    </script>
@endsection
