@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'prototype-dashboard-page')

@php
    $workspace = $dashboard['workspace'] ?? [];
    $founder = $dashboard['founder'] ?? auth()->user();
@endphp

@section('content')
    <x-os.prototype-shell :founder="$founder" :workspace="$workspace" active-tile="inbox">
        <div class="workspace">
            <div class="workspace-window" role="dialog" aria-label="Inbox">
                <div class="workspace-window-header">
                    <span class="traffic">
                        <span class="red"></span>
                        <span class="yellow"></span>
                        <span class="green"></span>
                    </span>
                    <span class="workspace-window-title">INBOX</span>
                </div>
                <div class="workspace-window-body">
                    <div class="empty-state">
                        <h2>Inbox zero</h2>
                        <p>Replies, notifications, and Atlas updates will land here.</p>
                    </div>
                </div>
            </div>
        </div>
    </x-os.prototype-shell>
@endsection
