@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'prototype-dashboard-page')

@php
    $founder = $dashboard['founder'];
    $workspace = $dashboard['workspace'];
    $notificationGroups = $workspace['notification_groups'] ?? ['new' => [], 'earlier' => []];
    $osEmbedMode = request()->boolean('os_embed');
@endphp

@section('head')
    <style>
        .notifications-stage { width:100%; max-width:1240px; margin:0 auto; }
        .notifications-heading {
            display:flex;
            align-items:center;
            gap:10px;
            margin:0 0 16px;
            font-size:18px;
            font-weight:600;
            color:var(--text);
        }
        .notifications-heading-dot {
            width:10px;
            height:10px;
            border-radius:50%;
            background:var(--accent-pink);
            box-shadow:0 0 0 6px rgba(242,84,107,0.10);
            flex:0 0 auto;
        }
        .notifications-divider {
            height:0;
            border-top:0.5px solid var(--border-strong);
            margin:8px auto 28px;
            width:60%;
        }
        .feed-filter { display:flex; gap:10px; margin-bottom:18px; }
        .feed-pill { padding:10px 14px; border-radius:999px; background:#fff; border:0.5px solid var(--border); color:var(--text-muted); font-size:13px; font-weight:600; }
        .feed-pill.active { background:var(--surface-2); color:var(--text); }
        .section-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.08em; color:var(--text-muted); margin:18px 0 10px; }
        .feed-list { display:grid; gap:12px; }
        .feed-item { display:flex; gap:14px; align-items:flex-start; background:#fff; border:0.5px solid var(--border); border-radius:16px; padding:14px 16px; box-shadow:var(--shadow-sm); }
        .feed-icon { width:40px; height:40px; border-radius:999px; display:grid; place-items:center; color:#fff; font-weight:700; flex-shrink:0; background:linear-gradient(135deg, #8e1c74, #ff2c35); }
        .feed-title { font-size:16px; font-weight:600; line-height:1.35; }
        .feed-time { color:var(--text-muted); margin-top:4px; font-size:13px; }
        .notifications-embed {
            padding: 20px 22px 24px;
            background: var(--surface);
            min-height: 100%;
        }
    </style>
@endsection

@section('content')
    @php ob_start(); @endphp
    <div class="{{ $osEmbedMode ? 'notifications-embed' : 'workspace' }}">
        <div class="notifications-stage">
            <div class="notifications-heading">
                <span class="notifications-heading-dot"></span>
                <span>Notifications</span>
            </div>
            <div class="notifications-divider"></div>
            <div class="workspace-window-body" style="padding:0;">
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
                        <div class="empty-state">
                            <h2>No new notifications right now.</h2>
                            <p>You’re up to date.</p>
                        </div>
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
                        <div class="empty-state">
                            <h2>No earlier notifications yet.</h2>
                            <p>As more OS activity lands, it will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @php $notificationsContent = ob_get_clean(); @endphp

    @if ($osEmbedMode)
        {!! $notificationsContent !!}
    @else
        <x-os.prototype-shell :founder="$founder" :workspace="$workspace" active-tile="inbox">
            {!! $notificationsContent !!}
        </x-os.prototype-shell>
    @endif
@endsection
