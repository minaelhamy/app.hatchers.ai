@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'prototype-dashboard-page')

@php
    $founder = auth()->user();
    $osEmbedMode = request()->boolean('os_embed');
@endphp

@section('head')
    <style>
        .search-stage { width:100%; max-width:1240px; margin:0 auto; }
        .search-heading {
            display:flex;
            align-items:center;
            gap:10px;
            margin:0 0 16px;
            font-size:18px;
            font-weight:600;
            color:var(--text);
        }
        .search-heading-dot {
            width:10px;
            height:10px;
            border-radius:50%;
            background:var(--accent-pink);
            box-shadow:0 0 0 6px rgba(242,84,107,0.10);
            flex:0 0 auto;
        }
        .search-divider {
            height:0;
            border-top:0.5px solid var(--border-strong);
            margin:8px auto 28px;
            width:60%;
        }
        .search-panel { width:min(980px, calc(100% - 40px)); margin:0 auto; }
        .search-input-form { display:flex; gap:12px; margin-bottom:18px; }
        .search-input-form input { flex:1; min-width:0; border:0.5px solid var(--border); border-radius:14px; background:#fff; padding:14px 16px; font:inherit; font-size:14px; color:var(--text); }
        .search-btn { padding:12px 18px; border-radius:999px; border:0; background:#111110; color:#fff; font:inherit; font-size:13px; font-weight:600; cursor:pointer; }
        .search-help { margin:0 0 20px; color:var(--text-muted); font-size:13px; line-height:1.55; }
        .result-list { display:grid; gap:12px; }
        .result-card { display:block; background:#fff; border:0.5px solid var(--border); border-radius:16px; padding:16px 18px; text-decoration:none; color:inherit; box-shadow:var(--shadow-sm); }
        .result-type { display:inline-flex; padding:6px 10px; background:var(--surface-2); border-radius:999px; font-size:11px; font-weight:600; letter-spacing:.05em; text-transform:uppercase; color:var(--text-muted); }
        .result-title { margin:12px 0 6px; font-size:18px; font-weight:600; letter-spacing:-0.01em; }
        .result-copy { margin:0; color:var(--text-muted); font-size:13px; line-height:1.55; }
        .search-embed {
            padding: 20px 22px 24px;
            background: var(--surface);
            min-height: 100%;
        }
        @media (max-width: 980px) { .search-input-form { flex-direction:column; } }
    </style>
@endsection

@section('content')
    @php ob_start(); @endphp
    <div class="{{ $osEmbedMode ? 'search-embed' : 'workspace' }}">
        <div class="search-stage">
            <div class="search-heading">
                <span class="search-heading-dot"></span>
                <span>Search</span>
            </div>
            <div class="search-divider"></div>
            <div class="search-panel">
                <div class="workspace-window-body" style="padding:0;">
                    <form method="GET" action="{{ route('founder.search') }}" class="search-input-form">
                        <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Search tasks, campaigns, offers, leads, and anything already living in your OS...">
                        @if($osEmbedMode)<input type="hidden" name="os_embed" value="1">@endif
                        <button class="search-btn" type="submit">Search</button>
                    </form>
                    <p class="search-help">Search is for finding where something lives fast. Use it when you already know roughly what you need and want the shortest path back into action.</p>
                    <div class="result-list">
                        @forelse ($results as $result)
                            <a href="{{ $result['href'] }}" class="result-card">
                                <span class="result-type">{{ $result['type'] }}</span>
                                <h3 class="result-title">{{ $result['title'] }}</h3>
                                <p class="result-copy">{{ $result['description'] }}</p>
                            </a>
                        @empty
                            <div class="empty-state">
                                <h2>{{ $searchQuery !== '' ? 'No matches yet' : 'Start with a search' }}</h2>
                                <p>{{ $searchQuery !== '' ? 'Try a task title, campaign name, offer, or a phrase from your recent activity.' : 'Search your founder workspace from one place.' }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    @php $searchContent = ob_get_clean(); @endphp

    @if ($osEmbedMode)
        {!! $searchContent !!}
    @else
        <x-os.prototype-shell :founder="$founder" active-tile="ai-tools">
            {!! $searchContent !!}
        </x-os.prototype-shell>
    @endif
@endsection
