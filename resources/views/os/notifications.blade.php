@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page {
            padding: 0;
        }

        .notifications-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 220px minmax(0, 1fr) 220px;
            background: #f8f5ee;
        }

        .notifications-sidebar,
        .notifications-rightbar {
            background: rgba(255, 252, 247, 0.8);
            border-color: var(--line);
            border-style: solid;
            border-width: 0 1px 0 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .notifications-rightbar {
            border-width: 0 0 0 1px;
            background: rgba(255, 251, 246, 0.9);
        }

        .notifications-sidebar-inner,
        .notifications-rightbar-inner {
            padding: 22px 18px;
        }

        .notifications-brand {
            display: inline-block;
            margin-bottom: 24px;
        }

        .notifications-brand img {
            width: 168px;
            height: auto;
            display: block;
        }

        .notifications-nav {
            display: grid;
            gap: 6px;
        }

        .notifications-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 14px;
            text-decoration: none;
            color: var(--ink);
            font-size: 0.98rem;
        }

        .notifications-nav-item.active {
            background: #ece6db;
        }

        .notifications-nav-icon {
            width: 18px;
            text-align: center;
            color: var(--muted);
        }

        .notifications-sidebar-footer {
            margin-top: auto;
            padding: 18px;
            border-top: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .notifications-user {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .notifications-avatar {
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

        .notifications-main {
            padding: 26px 28px 24px;
        }

        .notifications-main-inner {
            max-width: 700px;
            margin: 0 auto;
        }

        .notifications-main h1 {
            font-size: clamp(2rem, 3vw, 3rem);
            letter-spacing: -0.02em;
            margin-bottom: 22px;
        }

        .notifications-filter {
            display: flex;
            gap: 12px;
            margin-bottom: 26px;
        }

        .notifications-filter-pill {
            padding: 10px 16px;
            border-radius: 999px;
            background: transparent;
            color: var(--muted);
            text-decoration: none;
            font-weight: 600;
        }

        .notifications-filter-pill.active {
            background: #ece6db;
            color: var(--ink);
        }

        .notification-section {
            margin-bottom: 26px;
        }

        .notification-section h2 {
            font-size: 1.05rem;
            margin-bottom: 14px;
        }

        .notification-feed {
            display: grid;
            gap: 16px;
        }

        .notification-feed-item {
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .notification-feed-icon {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            color: white;
            font-weight: 700;
            flex-shrink: 0;
        }

        .notification-feed-icon.mentor {
            background: linear-gradient(135deg, #b58d55, #ddb77b);
        }

        .notification-feed-icon.task,
        .notification-feed-icon.atlas {
            background: linear-gradient(135deg, #8e1c74, #ff2c35);
        }

        .notification-feed-icon.lms {
            background: linear-gradient(135deg, #4591ef, #63b4ff);
        }

        .notification-feed-icon.default,
        .notification-feed-icon.bazaar,
        .notification-feed-icon.servio {
            background: linear-gradient(135deg, #8e1c74, #ff2c35);
        }

        .notification-feed-title {
            font-size: 1rem;
            line-height: 1.4;
        }

        .notification-feed-time {
            color: var(--muted);
            margin-top: 4px;
            font-size: 0.94rem;
        }

        .notifications-rightbar h3 {
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

        .notification-mini-list,
        .tool-list {
            display: grid;
            gap: 10px;
            margin-top: 14px;
        }

        .notification-mini-item,
        .tool-item {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(220, 207, 191, 0.65);
            border-radius: 14px;
            padding: 12px 14px;
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

        @media (max-width: 1240px) {
            .notifications-shell {
                grid-template-columns: 220px 1fr;
            }

            .notifications-rightbar {
                display: none;
            }
        }

        @media (max-width: 900px) {
            .notifications-shell {
                grid-template-columns: 1fr;
            }

            .notifications-sidebar {
                min-height: auto;
                border-right: 0;
                border-bottom: 1px solid var(--line);
            }

            .notifications-sidebar-footer {
                display: none;
            }

            .notifications-main {
                padding: 20px 16px 24px;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $workspace = $dashboard['workspace'];
        $notifications = $workspace['notification_groups'];
        $calendar = $workspace['calendar'];
        $aiTools = $workspace['ai_tools'];
        $unreadNotificationCount = $workspace['unread_notification_count'];
    @endphp

    <div class="notifications-shell">
        <aside class="notifications-sidebar">
            <div class="notifications-sidebar-inner">
                <a class="notifications-brand" href="/dashboard/founder">
                    <img src="/brand/hatchers-ai-logo.png" alt="Hatchers AI">
                </a>

                <nav class="notifications-nav">
                    <a class="notifications-nav-item" href="/dashboard/founder">
                        <span class="notifications-nav-icon">⌂</span>
                        <span>Home</span>
                    </a>
                    <a class="notifications-nav-item" href="{{ route('founder.commerce') }}">
                        <span class="notifications-nav-icon">⌁</span>
                        <span>Launch Plan</span>
                    </a>
                    <a class="notifications-nav-item" href="{{ route('founder.ai-tools') }}">
                        <span class="notifications-nav-icon">✦</span>
                        <span>AI Tools</span>
                    </a>
                    <a class="notifications-nav-item" href="{{ route('founder.learning-plan') }}">
                        <span class="notifications-nav-icon">▣</span>
                        <span>Learning Plan</span>
                    </a>
                    <a class="notifications-nav-item" href="{{ route('founder.tasks') }}">
                        <span class="notifications-nav-icon">◌</span>
                        <span>Tasks</span>
                    </a>
                    <a class="notifications-nav-item" href="{{ route('founder.settings') }}">
                        <span class="notifications-nav-icon">⚙</span>
                        <span>Settings</span>
                    </a>
                </nav>
            </div>

            <div class="notifications-sidebar-footer">
                <div class="notifications-user">
                    <div class="notifications-avatar">{{ strtoupper(substr($founder->full_name, 0, 1)) }}</div>
                    <div>{{ $founder->full_name }}</div>
                </div>
                <form method="POST" action="/logout" style="margin: 0;">
                    @csrf
                    <button class="notifications-nav-icon" type="submit" style="border: 0; background: transparent; cursor: pointer;">↘</button>
                </form>
            </div>
        </aside>

        <main class="notifications-main">
            <div class="notifications-main-inner">
                <h1>Notifications</h1>

                <div class="notifications-filter">
                    <a class="notifications-filter-pill active" href="{{ route('founder.notifications') }}">All</a>
                    <span class="notifications-filter-pill">Unread</span>
                </div>

                <section class="notification-section">
                    <h2>New</h2>
                    <div class="notification-feed">
                        @forelse ($notifications['new'] as $notification)
                            <div class="notification-feed-item">
                                <div class="notification-feed-icon {{ in_array($notification['kind'], ['mentor', 'task', 'atlas', 'lms', 'bazaar', 'servio'], true) ? $notification['kind'] : 'default' }}">
                                    {{ strtoupper(substr($notification['kind'], 0, 1)) }}
                                </div>
                                <div>
                                    <div class="notification-feed-title">{{ $notification['title'] }}</div>
                                    <div class="notification-feed-time">{{ $notification['age_label'] }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="notification-feed-item">
                                <div class="notification-feed-icon default">i</div>
                                <div>
                                    <div class="notification-feed-title">No new notifications right now.</div>
                                    <div class="notification-feed-time">You’re up to date.</div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="notification-section">
                    <h2>Earlier</h2>
                    <div class="notification-feed">
                        @forelse ($notifications['earlier'] as $notification)
                            <div class="notification-feed-item">
                                <div class="notification-feed-icon {{ in_array($notification['kind'], ['mentor', 'task', 'atlas', 'lms', 'bazaar', 'servio'], true) ? $notification['kind'] : 'default' }}">
                                    {{ strtoupper(substr($notification['kind'], 0, 1)) }}
                                </div>
                                <div>
                                    <div class="notification-feed-title">{{ $notification['title'] }}</div>
                                    <div class="notification-feed-time">{{ $notification['age_label'] }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="notification-feed-item">
                                <div class="notification-feed-icon default">i</div>
                                <div>
                                    <div class="notification-feed-title">No earlier notifications yet.</div>
                                    <div class="notification-feed-time">As Hatchers Ai OS syncs activity, it will appear here.</div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </main>

        <aside class="notifications-rightbar">
            <div class="notifications-rightbar-inner">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                    <h3 style="margin: 0;">Notifications</h3>
                    <div class="notification-badge">{{ $unreadNotificationCount }}</div>
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

                <div class="notification-mini-list">
                    @foreach (array_slice(array_merge($notifications['new'], $notifications['earlier']), 0, 2) as $notification)
                        <div class="notification-mini-item">
                            <div style="font-weight: 600;">{{ $notification['title'] }}</div>
                            <div style="margin-top: 4px; color: var(--muted);">{{ $notification['age_label'] }}</div>
                        </div>
                    @endforeach
                </div>

                <h3 id="notifications-tools" style="margin-top: 22px;">AI Tools</h3>
                <div class="tool-list">
                    @foreach ($aiTools as $tool)
                        <div class="tool-item">
                            <div class="tool-icon">□</div>
                            <div>{{ $tool['title'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>
@endsection
