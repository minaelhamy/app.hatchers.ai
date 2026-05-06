@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'prototype-dashboard-page')

@php
    $workspace = $dashboard['workspace'] ?? [];
    $founder = $dashboard['founder'] ?? auth()->user();
    $osEmbedMode = request()->boolean('os_embed');
@endphp

@section('head')
    <style>
        .inbox-stage {
            width: 100%;
            max-width: 1240px;
            margin: 0 auto;
        }
        .inbox-heading {
            display:flex;
            align-items:center;
            gap:10px;
            margin:0 0 16px;
            font-size:18px;
            font-weight:600;
            color:var(--text);
        }
        .inbox-heading-dot {
            width:10px;
            height:10px;
            border-radius:50%;
            background:var(--accent-pink);
            box-shadow:0 0 0 6px rgba(242,84,107,0.10);
            flex:0 0 auto;
        }
        .inbox-divider {
            height:0;
            border-top:0.5px solid var(--border-strong);
            margin:8px auto 0;
            width:60%;
        }
        .inbox-empty {
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:flex-start;
            text-align:center;
            padding-top:56px;
            gap:4px;
            min-height:320px;
        }
        .inbox-empty h2 {
            margin:0;
            font-size:14px;
            font-weight:600;
            color:var(--text);
            letter-spacing:-0.005em;
            white-space:nowrap;
        }
        .inbox-empty p {
            margin:0;
            font-size:13px;
            color:var(--text-subtle);
            font-weight:400;
            white-space:nowrap;
        }
        .inbox-embed {
            padding: 24px;
            background: #fff;
            min-height: 100%;
        }
    </style>
@endsection

@section('content')
    @if ($osEmbedMode)
        <div class="inbox-embed">
            <div class="inbox-stage">
                <div class="inbox-heading">
                    <span class="inbox-heading-dot"></span>
                    <span>Inbox</span>
                </div>
                <div class="inbox-divider"></div>
                <div class="inbox-empty">
                    <h2>Inbox zero</h2>
                    <p>Replies, notifications, and Atlas updates will land here.</p>
                </div>
            </div>
        </div>
    @else
        <x-os.prototype-shell :founder="$founder" :workspace="$workspace" active-tile="inbox">
            <div class="workspace">
                <div class="inbox-stage">
                    <div class="inbox-heading">
                        <span class="inbox-heading-dot"></span>
                        <span>Inbox</span>
                    </div>
                    <div class="inbox-divider"></div>
                    <div class="inbox-empty">
                        <h2>Inbox zero</h2>
                        <p>Replies, notifications, and Atlas updates will land here.</p>
                    </div>
                </div>
            </div>
        </x-os.prototype-shell>
    @endif
@endsection
