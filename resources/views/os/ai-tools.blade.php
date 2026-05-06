@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'prototype-dashboard-page')

@php
    $founder = $dashboard['founder'] ?? auth()->user();
    $company = $dashboard['company'] ?? null;
    $workspace = $dashboard['workspace'] ?? [];
    $toolLinks = [
        ['label' => 'Tasks', 'href' => route('founder.tasks')],
        ['label' => 'Inbox', 'href' => route('founder.inbox')],
        ['label' => 'Website', 'href' => route('website')],
        ['label' => 'Marketing', 'href' => route('founder.marketing')],
        ['label' => 'Commerce', 'href' => route('founder.commerce')],
        ['label' => 'Analytics', 'href' => route('founder.analytics')],
        ['label' => 'Settings', 'href' => route('founder.settings')],
        ['label' => 'Media Library', 'href' => route('founder.media-library')],
    ];
@endphp

@section('head')
    <style>
        .agent-panel { width:min(760px, calc(100% - 40px)); margin:36px auto 0; background:var(--surface); border:0.5px solid var(--border); border-radius:24px; box-shadow:0 32px 64px rgba(30,24,16,0.18), 0 6px 20px rgba(30,24,16,0.08); padding:32px 32px 28px; position:relative; }
        .agent-heading { margin:0 0 22px; display:flex; align-items:center; gap:12px; font-size:34px; line-height:1.05; letter-spacing:-0.03em; font-weight:650; }
        .agent-orb { width:14px; height:14px; border-radius:50%; background:var(--accent-pink); box-shadow:0 0 0 8px rgba(242,84,107,0.12); }
        .agent-composer { border:0.5px solid var(--border); background:#fff; border-radius:18px; min-height:120px; padding:20px; display:flex; align-items:flex-start; justify-content:space-between; gap:16px; box-shadow:var(--shadow-sm); }
        .agent-composer-prompt { color:var(--text-subtle); font-size:20px; line-height:1.35; }
        .agent-composer-add { width:36px; height:36px; border-radius:50%; border:0.5px solid var(--border); background:var(--surface); color:var(--text-muted); font-size:22px; line-height:1; cursor:pointer; }
        .agent-actions { margin-top:18px; display:flex; flex-wrap:wrap; gap:10px; }
        .agent-chip { padding:10px 14px; background:#fff; border:0.5px solid var(--border); border-radius:999px; font-size:13px; font-weight:500; color:var(--text); text-decoration:none; box-shadow:var(--shadow-sm); }
        .agent-subcopy { margin:14px 0 0; color:var(--text-muted); font-size:13px; line-height:1.55; max-width:580px; }
        @media (max-width: 980px) { .content { grid-template-columns:1fr; } .tile-rail { flex-direction:row; justify-content:center; padding:20px; } .workspace { padding:20px 20px 60px; } .agent-panel { width: calc(100% - 10px); margin-top: 12px; padding: 22px 20px 22px; } .agent-heading { font-size: 28px; } .agent-composer-prompt { font-size: 18px; } }
    </style>
@endsection

@section('content')
    <x-os.prototype-shell :founder="$founder" :workspace="$workspace" active-tile="ai-tools">
        <div class="workspace">
            <div class="agent-panel">
                <h1 class="agent-heading">
                    <span class="agent-orb"></span>
                    <span>What are we achieving today?</span>
                </h1>
                <div class="agent-composer">
                    <div class="agent-composer-prompt">How can we help you today?</div>
                    <button class="agent-composer-add" type="button" aria-label="Add">＋</button>
                </div>
                <p class="agent-subcopy">Open the exact Hatchers tools you need from one prototype-style AI surface instead of bouncing through the older workspace views.</p>
                <div class="agent-actions">
                    @foreach($toolLinks as $tool)
                        <a href="{{ $tool['href'] }}" class="agent-chip">{{ $tool['label'] }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </x-os.prototype-shell>
@endsection
