@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'prototype-dashboard-page')

@php
    $workspace = $dashboard['workspace'] ?? [];
    $notifications = $workspace['notifications'] ?? [];
    $founder = $dashboard['founder'] ?? auth()->user();
    $company = $dashboard['company'] ?? null;
    $taskEntries = $launchPlanState['tasks'] ?? [];
    $launchMilestones = $launchPlanState['milestones'] ?? [];
    $quickPrompt = $workspace['quick_prompt'] ?? 'Start chatting...';
    $chatNeedsOnboarding = (bool) ($chatOnboardingState['needs_onboarding'] ?? false);
    $projectName = trim((string) ($chatOnboardingState['project_name'] ?? ($company?->company_name ?? 'New Project')));
    $hasProject = !$chatNeedsOnboarding && $projectName !== '' && strcasecmp($projectName, 'New Project') !== 0;
    $projectCards = $hasProject ? [[
        'name' => $projectName,
        'meta' => $launchPlanState['title'] ?? ($company?->business_model ? ucfirst((string) $company->business_model) : 'Project'),
    ]] : [];
    $toolLinks = [
        ['label' => 'Tasks', 'href' => route('founder.tasks')],
        ['label' => 'Inbox', 'href' => route('founder.inbox')],
        ['label' => 'Website', 'href' => route('website')],
        ['label' => 'Marketing', 'href' => route('founder.marketing')],
        ['label' => 'Commerce', 'href' => route('founder.commerce')],
        ['label' => 'AI Studio', 'href' => route('founder.ai-tools')],
        ['label' => 'Analytics', 'href' => route('founder.analytics')],
        ['label' => 'Settings', 'href' => route('founder.settings')],
    ];
    $recentTaskLabels = collect($taskEntries)->pluck('title')->filter()->take(3)->values()->all();
    $recentNotificationLabels = collect($notifications)->map(function ($item) {
        return $item['title'] ?? $item['body'] ?? $item['description'] ?? null;
    })->filter()->take(3)->values()->all();
@endphp

@section('head')
    <style>
        .page.prototype-dashboard-page {
            --bg: #F9F8F6;
            --surface: #FBFAF7;
            --surface-2: #F4F1EC;
            --surface-3: #EFEAE3;
            --border: rgba(30, 24, 16, 0.10);
            --border-strong: rgba(30, 24, 16, 0.16);
            --hairline: rgba(30, 24, 16, 0.08);
            --text: #1B1A17;
            --text-muted: #6B6660;
            --text-subtle: #A39E96;
            --black: #111110;
            --accent-pink: #F2546B;
            --tile-purple: #C8B8D6;
            --tile-purple-2: #A99BBC;
            --tile-grey: #B8B0A6;
            --tile-grey-2: #8E867C;
            --shadow-sm: 0 1px 0 rgba(30,24,16,0.04);
            --shadow-md: 0 1px 2px rgba(30,24,16,0.06), 0 0 0 0.5px rgba(30,24,16,0.06);
            --shadow-lg: 0 8px 24px rgba(30,24,16,0.08), 0 1px 2px rgba(30,24,16,0.06);
            min-height: 100vh;
            padding: 0;
            background: var(--bg);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
        }

        .page.prototype-dashboard-page * { box-sizing: border-box; }

        .prototype-app {
            background: var(--bg);
            display: grid;
            grid-template-columns: auto 1fr;
            min-height: 100vh;
            position: relative;
        }

        .rail {
            width: 56px;
            border-right: 0.5px solid var(--hairline);
            padding: 14px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            background: var(--bg);
        }

        .rail-top, .rail-bottom {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }

        .rail-icon {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #6B6660;
            cursor: pointer;
            border-radius: 6px;
            background: transparent;
            border: 0;
            padding: 0;
            position: relative;
            text-decoration: none;
            font-size: 16px;
        }

        .rail-icon:hover { color: var(--text); background: var(--surface-2); }

        .rail-add.is-active {
            background: #ECE6FA;
            color: #5B45C9;
            border: 0.5px solid #C9BCF0;
        }

        .rail-tooltip {
            position: absolute;
            left: calc(100% + 10px);
            top: 50%;
            transform: translateY(-50%);
            background: #fff;
            border: 0.5px solid var(--border);
            border-radius: 8px;
            padding: 5px 10px;
            font-size: 12px;
            color: var(--text);
            white-space: nowrap;
            box-shadow: var(--shadow-md);
            opacity: 0;
            pointer-events: none;
            transition: opacity .12s ease;
        }

        .rail-add.is-active .rail-tooltip,
        .rail-add:hover .rail-tooltip { opacity: 1; }

        .rail-avatar {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: linear-gradient(160deg, #7C5BE0, #5B3FC9);
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .sidepane {
            width: 250px;
            border-right: 0.5px solid var(--hairline);
            background: var(--bg);
            display: none;
            flex-direction: column;
            padding: 14px;
            gap: 10px;
            min-height: 100vh;
            overflow-y: auto;
        }

        .sidepane.is-open { display: flex; }
        .rail.is-hidden { display: none; }

        .sidepane-head {
            display: flex;
            justify-content: flex-end;
        }

        .sidepane-close {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: transparent;
            border: 1px solid #C9BCF0;
            color: #5B45C9;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .sidepane-segment {
            display: flex;
            background: var(--surface-2);
            border-radius: 10px;
            padding: 3px;
            gap: 2px;
        }

        .seg-btn {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: transparent;
            border: 0;
            border-radius: 8px;
            padding: 7px 8px;
            font: inherit;
            font-size: 13px;
            color: var(--text-muted);
            cursor: pointer;
        }

        .seg-btn.is-active {
            background: var(--bg);
            color: var(--text);
            box-shadow: 0 1px 2px rgba(20,16,10,0.05);
        }

        .seg-badge {
            background: #5B45C9;
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 999px;
            letter-spacing: 0.04em;
        }

        .sidepane-row {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: transparent;
            border: 0;
            padding: 8px 6px;
            border-radius: 8px;
            font: inherit;
            font-size: 13.5px;
            color: var(--text);
            cursor: pointer;
            text-align: left;
            text-decoration: none;
        }

        .sidepane-row:hover,
        .sidepane-recent-item:hover { background: var(--surface-2); }

        .sidepane-search {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--bg);
            border: 0.5px solid var(--border);
            border-radius: 10px;
            padding: 7px 10px;
            margin-top: 4px;
        }

        .sidepane-search input {
            flex: 1;
            border: 0;
            background: transparent;
            outline: none;
            font: inherit;
            font-size: 13px;
            color: var(--text);
        }

        .sidepane-section-label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: capitalize;
            margin: 8px 6px 2px;
        }

        .sidepane-recent { display: flex; flex-direction: column; }

        .sidepane-recent-item {
            background: transparent;
            border: 0;
            padding: 7px 6px;
            border-radius: 6px;
            text-align: left;
            font: inherit;
            font-size: 13px;
            color: var(--text);
            cursor: pointer;
        }

        .sidepane-spacer { flex: 1 1 auto; }

        .sidepane-upgrade {
            background: var(--surface-2);
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .sidepane-upgrade-title { font-size: 13px; font-weight: 600; color: var(--text); }
        .sidepane-upgrade-sub { font-size: 11.5px; color: var(--text-muted); }

        .sidepane-upgrade-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(160deg, #7C5BE0, #5B3FC9);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sidepane-news { position: relative; }

        .sidepane-news-dot {
            width: 6px;
            height: 6px;
            background: #5B45C9;
            border-radius: 50%;
            margin-left: auto;
        }

        .sidepane-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 4px 6px;
            border-top: 0.5px solid var(--hairline);
        }

        .sidepane-user-info { flex: 1; min-width: 0; }
        .sidepane-user-name { font-size: 13px; font-weight: 600; color: var(--text); }
        .sidepane-user-email {
            font-size: 11.5px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .main {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .topbar {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 16px;
            padding: 14px 20px;
            border-bottom: 0.5px solid var(--hairline);
            background: var(--bg);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px 6px 8px;
            background: var(--surface);
            border: 0.5px solid var(--border);
            border-radius: 999px;
            box-shadow: var(--shadow-sm);
            font-weight: 600;
            font-size: 13px;
            color: var(--text);
            white-space: nowrap;
            text-decoration: none;
        }

        .brand-mark {
            width: 18px;
            height: 18px;
            border-radius: 5px;
            background: var(--accent-pink);
            box-shadow: inset 0 0 0 0.5px rgba(0,0,0,0.06);
        }

        .search {
            display: flex;
            align-items: center;
            gap: 10px;
            height: 36px;
            padding: 0 14px;
            background: var(--surface);
            border: 0.5px solid var(--border);
            border-radius: 999px;
            box-shadow: var(--shadow-sm);
            max-width: 560px;
            width: 100%;
            justify-self: start;
            margin-left: 4px;
        }

        .search-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #1B1A17;
            flex: 0 0 auto;
        }

        .search input {
            flex: 1;
            border: 0;
            outline: 0;
            background: transparent;
            font: inherit;
            color: var(--text);
            font-size: 13px;
        }

        .search input::placeholder { color: var(--text-subtle); }

        .search-kbd {
            font-size: 11px;
            color: var(--text-subtle);
            border: 0.5px solid var(--border);
            border-radius: 6px;
            padding: 2px 7px;
            line-height: 1;
            letter-spacing: 0.02em;
        }

        .topbar-right {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 6px 14px 6px 10px;
            background: var(--surface);
            border: 0.5px solid var(--border);
            border-radius: 999px;
            box-shadow: var(--shadow-sm);
            font-size: 12.5px;
            color: var(--text);
            white-space: nowrap;
        }

        .bell-wrap {
            position: relative;
            width: 22px;
            height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #4D4944;
        }

        .bell-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            min-width: 14px;
            height: 14px;
            padding: 0 3px;
            border-radius: 999px;
            background: var(--accent-pink);
            color: #fff;
            font-size: 9px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            border: 1.5px solid var(--surface);
        }

        .content {
            flex: 1;
            display: grid;
            grid-template-columns: 140px 1fr;
            min-height: 0;
            position: relative;
        }

        .tile-rail {
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            align-items: center;
        }

        .tile {
            width: 92px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .tile-art {
            width: 88px;
            height: 88px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.35),
                inset 0 -10px 24px rgba(0,0,0,0.12),
                0 1px 2px rgba(30,24,16,0.08);
            position: relative;
            overflow: hidden;
            font-size: 28px;
        }

        .tile-art::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(160deg, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0) 45%, rgba(0,0,0,0.10) 100%);
            pointer-events: none;
        }

        .tile-art.purple { background: linear-gradient(160deg, var(--tile-purple) 0%, var(--tile-purple-2) 100%); }
        .tile-art.grey { background: linear-gradient(160deg, var(--tile-grey) 0%, var(--tile-grey-2) 100%); }

        .tile-label {
            font-size: 12px;
            color: var(--text);
            font-weight: 500;
            text-align: center;
        }

        .workspace {
            padding: 28px 40px 60px;
            display: flex;
            flex-direction: column;
            min-height: 100%;
            position: relative;
        }

        .workspace-header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 28px;
            min-height: 40px;
        }

        .new-project-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: var(--black);
            color: #fff;
            border: 0;
            border-radius: 999px;
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            flex: 0 0 auto;
            box-shadow: 0 4px 12px rgba(17,17,16,0.18), 0 1px 0 rgba(255,255,255,0.08) inset;
        }

        .new-project-btn:hover { background: #000; }
        .new-project-btn .plus { font-size: 14px; line-height: 1; font-weight: 500; margin-right: -2px; }

        .divider {
            height: 0;
            border-top: 0.5px solid var(--border-strong);
            width: 60%;
            align-self: center;
        }

        .empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            text-align: center;
            padding-top: 40px;
            gap: 4px;
        }

        .empty-state h2 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            letter-spacing: -0.005em;
            white-space: nowrap;
        }

        .empty-state p {
            margin: 0;
            font-size: 13px;
            color: var(--text-subtle);
            font-weight: 400;
            white-space: nowrap;
        }

        .project-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 160px));
            gap: 14px;
            margin-top: 28px;
        }

        .project-card {
            background: var(--surface);
            border: 0.5px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            cursor: pointer;
            transition: box-shadow .15s ease, transform .15s ease;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
        }

        .project-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .project-card-art {
            height: 88px;
            background: linear-gradient(160deg, #F2EEE6 0%, #E6E0D4 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .project-card-art .folder {
            width: 64px;
            height: 50px;
            background: #fff;
            border: 0.5px solid rgba(0,0,0,0.08);
            border-radius: 6px;
            position: relative;
            box-shadow: 0 6px 14px rgba(30,24,16,0.08);
        }

        .project-card-art .folder::before {
            content: "";
            position: absolute;
            top: -6px;
            left: 8px;
            width: 22px;
            height: 8px;
            background: #fff;
            border: 0.5px solid rgba(0,0,0,0.08);
            border-bottom: 0;
            border-radius: 4px 4px 0 0;
        }

        .project-card-body { padding: 14px 16px 16px; }
        .project-card-title { margin: 0 0 4px; font-size: 14px; font-weight: 600; color: var(--text); }
        .project-card-meta { margin: 0; font-size: 12px; color: var(--text-muted); }

        .fab {
            position: absolute;
            bottom: 28px;
            right: 28px;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #FAD7DC;
            border: 0.5px solid rgba(242,84,107,0.25);
            box-shadow: 0 6px 18px rgba(242,84,107,0.25), inset 0 1px 0 rgba(255,255,255,0.5);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
        }

        .fab-dot {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--accent-pink);
            box-shadow: 0 1px 2px rgba(242,84,107,0.4);
            transition: all 160ms ease;
        }

        .fab.is-active .fab-dot {
            width: 14px;
            height: 14px;
            border-radius: 4px;
        }

        .chat-card {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: min(720px, calc(100% - 64px));
            background: var(--surface);
            border: 0.5px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 32px 64px rgba(30,24,16,0.14), 0 4px 10px rgba(30,24,16,0.06), 0 0 0 0.5px rgba(30,24,16,0.04);
            overflow: hidden;
            z-index: 20;
            display: none;
            flex-direction: column;
            cursor: pointer;
        }

        body[data-chat="card"] .chat-card { display: flex; }

        .chat-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-bottom: 0.5px solid var(--hairline);
            background: var(--surface);
        }

        .traffic {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .traffic span {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            box-shadow: inset 0 0 0 0.5px rgba(0,0,0,0.10);
            cursor: pointer;
            display: inline-block;
        }

        .traffic .red { background: #ED6A5E; }
        .traffic .yellow { background: #F4BF4F; }
        .traffic .green { background: #62C554; }

        .chat-card-title {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.10em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .chat-card-body {
            padding: 28px 28px 22px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            background: var(--surface);
            min-height: 320px;
        }

        .chat-card-prompt {
            font-size: 22px;
            color: var(--text);
            font-weight: 600;
            letter-spacing: -0.01em;
            line-height: 1.3;
        }

        .chat-card-input-row {
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 6px 4px;
            border-top: 0.5px solid var(--hairline);
            color: var(--text-subtle);
        }

        .icon-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 0.5px solid var(--border);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 14px;
        }

        .chat-panel {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 560px;
            max-width: 62%;
            background: var(--bg);
            border-left: 0.5px solid var(--hairline);
            display: none;
            flex-direction: column;
            z-index: 15;
            box-shadow: -16px 0 40px -24px rgba(30,24,16,0.18);
            transition: width 200ms ease, max-width 200ms ease;
        }

        body[data-chat="panel"] .chat-panel { display: flex; }

        .panel-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 18px;
            border-bottom: 0.5px solid var(--hairline);
            min-width: 0;
        }

        .panel-traffic {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-right: 4px;
        }

        .panel-traffic span {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            box-shadow: inset 0 0 0 0.5px rgba(0,0,0,0.10);
            display: inline-block;
            cursor: pointer;
        }

        .panel-traffic .red { background: #ED6A5E; }
        .panel-traffic .yellow { background: #F4BF4F; }
        .panel-traffic .green { background: #62C554; }

        .breadcrumb {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .crumb-current {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text);
            font-weight: 500;
            background: var(--surface-2);
            padding: 4px 10px;
            border-radius: 6px;
            border: 0.5px solid var(--hairline);
        }

        .panel-stream {
            flex: 1;
            overflow-y: auto;
            padding: 22px 24px 16px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .msg-row { display: flex; }
        .msg-row.user { justify-content: flex-end; }
        .msg-row.ai { justify-content: flex-start; }

        .bubble-user {
            background: #2A2724;
            color: #F1EEE8;
            font-size: 13px;
            padding: 10px 16px;
            border-radius: 18px;
            max-width: 80%;
            line-height: 1.45;
            box-shadow: 0 1px 2px rgba(30,24,16,0.10);
        }

        .ai-block {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            max-width: 92%;
        }

        .ai-avatar {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--accent-pink);
            flex: 0 0 auto;
            margin-top: 2px;
            box-shadow: inset 0 0 0 0.5px rgba(0,0,0,0.06);
        }

        .ai-text {
            font-size: 13px;
            color: var(--text);
            line-height: 1.55;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .ai-text p { margin: 0; }

        .choice-list,
        .chat-actions {
            margin-top: 6px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .choice {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            background: var(--surface);
            border: 0.5px solid var(--border);
            border-radius: 10px;
            font: inherit;
            font-size: 13px;
            color: var(--text);
            cursor: pointer;
            text-align: left;
            box-shadow: var(--shadow-sm);
            transition: background 120ms ease, border-color 120ms ease;
        }

        .choice:hover { background: var(--surface-2); border-color: var(--border-strong); }

        .thinking {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            padding: 0 4px;
            font-size: 12.5px;
            color: var(--text-subtle);
            font-style: italic;
        }

        .thinking-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--accent-pink);
            animation: pulse 1.4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.35; transform: scale(0.85); }
            50% { opacity: 1; transform: scale(1.1); }
        }

        .panel-composer {
            padding: 18px;
            border-top: 0.5px solid var(--hairline);
            background: var(--surface);
        }

        .composer-box {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px 10px 16px;
            border: 0.5px solid var(--border);
            border-radius: 14px;
            background: #fff;
        }

        .composer-box textarea {
            flex: 1;
            min-height: 22px;
            max-height: 140px;
            resize: none;
            border: 0;
            outline: none;
            background: transparent;
            font: inherit;
            color: var(--text);
        }

        .composer-send {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            border: 0;
            background: var(--black);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .composer-foot {
            margin-top: 10px;
            display: flex;
            justify-content: flex-start;
        }

        .agent-overlay {
            position: absolute;
            inset: 0;
            background: rgba(20, 16, 10, 0.18);
            backdrop-filter: blur(2px);
            z-index: 70;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .agent-overlay.is-open { display: flex; }

        .agent-panel {
            background: var(--bg);
            border-radius: 16px;
            width: 100%;
            max-width: 760px;
            padding: 60px 48px 48px;
            position: relative;
            box-shadow: 0 24px 60px rgba(20,16,10,0.18);
            border: 0.5px solid var(--border);
        }

        .agent-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: transparent;
            border: 0;
            cursor: pointer;
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .agent-heading {
            font-size: 30px;
            font-weight: 600;
            color: var(--text);
            line-height: 1.2;
            text-align: center;
            margin: 0 0 32px;
            display: inline-flex;
            align-items: center;
            gap: 14px;
            width: 100%;
            justify-content: center;
        }

        .agent-orb {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, #E9DFFF 0%, #B7A6E8 60%, #8E78D4 100%);
            box-shadow: inset 0 -3px 6px rgba(0,0,0,0.12);
            flex-shrink: 0;
        }

        .agent-composer {
            background: var(--surface);
            border: 0.5px solid var(--border);
            border-radius: 14px;
            padding: 18px 20px 14px;
            margin-bottom: 18px;
        }

        .agent-composer-prompt {
            font-size: 15px;
            color: var(--text-muted);
            margin-bottom: 14px;
        }

        .agent-composer-add {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: transparent;
            border: 0;
            color: var(--text-muted);
            cursor: pointer;
        }

        .agent-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .agent-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--bg);
            border: 0.5px solid var(--border);
            border-radius: 999px;
            padding: 8px 14px;
            font: inherit;
            font-size: 13px;
            color: var(--text);
            cursor: pointer;
            text-decoration: none;
        }

        .agent-chip:hover { background: var(--surface-2); }

        @media (max-width: 1080px) {
            .content {
                grid-template-columns: 1fr;
            }

            .tile-rail {
                flex-direction: row;
                justify-content: flex-start;
                padding: 18px 18px 0;
            }

            .workspace {
                padding-top: 18px;
            }
        }

        @media (max-width: 860px) {
            .prototype-app {
                grid-template-columns: 1fr;
            }

            .rail,
            .sidepane { display: none !important; }

            .topbar {
                grid-template-columns: 1fr;
                justify-items: stretch;
            }

            .search { max-width: none; }

            .content { grid-template-columns: 1fr; }

            .workspace {
                padding: 18px 16px 40px;
            }

            .chat-panel {
                width: 100%;
                max-width: 100%;
            }

            .agent-overlay {
                padding: 16px;
            }

            .agent-panel {
                padding: 54px 20px 24px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="prototype-app"
         id="guidebookShell"
         data-onboarding-needed="{{ $chatNeedsOnboarding ? '1' : '0' }}"
         data-onboarding-endpoint="{{ route('assistant.chat.onboarding-complete') }}"
         data-reset-endpoint="{{ route('assistant.chat.reset') }}"
         data-assistant-endpoint="{{ route('assistant.chat') }}">

        <aside class="rail" id="leftRail">
            <div class="rail-top">
                <button type="button" class="rail-icon" id="openSidebarBtn" aria-label="Open sidebar">▥</button>
                <a href="{{ route('founder.settings') }}" class="rail-icon" aria-label="Settings">⚙</a>
                <button type="button" class="rail-icon rail-add" id="railAiToolsBtn" aria-label="New Agent">
                    ＋
                    <span class="rail-tooltip">New Agent</span>
                </button>
            </div>
            <div class="rail-bottom">
                <a href="{{ route('founder.inbox') }}" class="rail-icon" aria-label="Inbox">✉</a>
                <span class="rail-avatar" aria-label="Profile">{{ strtoupper(substr((string) ($founder->full_name ?? 'J'), 0, 1)) }}</span>
            </div>
        </aside>

        <aside class="sidepane" id="sidepane">
            <div class="sidepane-head">
                <button type="button" class="sidepane-close" id="closeSidebarBtn" aria-label="Collapse sidebar">▥</button>
            </div>

            <div class="sidepane-segment">
                <button type="button" class="seg-btn">Browse</button>
                <button type="button" class="seg-btn is-active">Agent <span class="seg-badge">NEW</span></button>
            </div>

            <a href="{{ route('founder.settings') }}" class="sidepane-row">Customize <span class="seg-badge">NEW</span></a>
            <button type="button" class="sidepane-row" id="sidepaneNewAgentBtn">＋ New Agent</button>

            <div class="sidepane-search">
                <span>⌕</span>
                <input type="text" placeholder="Search chats…">
            </div>

            <div class="sidepane-section-label">Recent</div>
            <div class="sidepane-recent">
                @foreach($recentTaskLabels as $label)
                    <button type="button" class="sidepane-recent-item">{{ $label }}</button>
                @endforeach
                @foreach($recentNotificationLabels as $label)
                    <button type="button" class="sidepane-recent-item">{{ $label }}</button>
                @endforeach
                @if(empty($recentTaskLabels) && empty($recentNotificationLabels))
                    <button type="button" class="sidepane-recent-item">{{ $projectName }}</button>
                @endif
            </div>

            <div class="sidepane-spacer"></div>

            <div class="sidepane-upgrade">
                <div>
                    <div class="sidepane-upgrade-title">Upgrade</div>
                    <div class="sidepane-upgrade-sub">Unlock unlimited generations</div>
                </div>
                <span class="sidepane-upgrade-icon">✦</span>
            </div>

            <a href="{{ route('founder.notifications') }}" class="sidepane-row sidepane-news">What's new <span class="sidepane-news-dot"></span></a>

            <div class="sidepane-user">
                <span class="rail-avatar">{{ strtoupper(substr((string) ($founder->full_name ?? 'J'), 0, 1)) }}</span>
                <div class="sidepane-user-info">
                    <div class="sidepane-user-name">{{ $founder->full_name }}</div>
                    <div class="sidepane-user-email">{{ $founder->email }}</div>
                </div>
                <span>⇅</span>
            </div>
        </aside>

        <div class="main">
            <div class="topbar">
                <a href="{{ route('dashboard') }}" class="brand">
                    <span class="brand-mark"></span>
                    <span>Hatchers AI OS</span>
                </a>

                <div class="search">
                    <span class="search-dot"></span>
                    <input type="text" placeholder="What would you like to do?">
                    <span class="search-kbd">⌘K</span>
                </div>

                <div class="topbar-right">
                    <a href="{{ route('founder.notifications') }}" class="status-pill">
                        <span class="bell-wrap">
                            <span>🔔</span>
                            @if(!empty($workspace['unread_notification_count']))
                                <span class="bell-badge">{{ $workspace['unread_notification_count'] }}</span>
                            @endif
                        </span>
                        <span>{{ now()->format('D, M j g:i A') }}</span>
                    </a>
                </div>
            </div>

            <div class="content">
                <div class="tile-rail">
                    <a class="tile" href="{{ route('founder.tasks') }}">
                        <div class="tile-art purple">☷</div>
                        <div class="tile-label">Tasks</div>
                    </a>
                    <a class="tile" href="{{ route('founder.inbox') }}">
                        <div class="tile-art grey">⌂</div>
                        <div class="tile-label">Inbox</div>
                    </a>
                    <button class="tile" type="button" id="openToolsBtn" style="border:0;background:transparent;padding:0;">
                        <div class="tile-art grey">✦</div>
                        <div class="tile-label">AI Tools</div>
                    </button>
                </div>

                <div class="workspace">
                    <div class="workspace-header">
                        <button class="new-project-btn" id="newProjectBtn" type="button">
                            <span class="plus">+</span>
                            <span>New Project</span>
                        </button>
                    </div>

                    <div class="divider"></div>

                    @if (!$hasProject)
                        <div class="empty-state">
                            <h2>You have no projects yet</h2>
                            <p>Start by creating a new project</p>
                        </div>
                    @else
                        <div class="project-grid">
                            @foreach($projectCards as $project)
                                <a href="{{ route('dashboard') }}" class="project-card" title="{{ $project['name'] }}">
                                    <div class="project-card-art"><div class="folder"></div></div>
                                    <div class="project-card-body">
                                        <h3 class="project-card-title">{{ $project['name'] }}</h3>
                                        <p class="project-card-meta">{{ $project['meta'] }}</p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <button class="fab" id="chatFab" type="button" aria-label="Toggle chat">
                        <span class="fab-dot"></span>
                    </button>

                    <div class="chat-card" id="chatCard" role="dialog" aria-label="New founder chat">
                        <div class="chat-card-header">
                            <span class="traffic">
                                <span class="red" data-action="close" title="Close"></span>
                                <span class="yellow" data-action="minimize" title="Minimize"></span>
                                <span class="green" data-action="maximize" title="Maximize"></span>
                            </span>
                            <span class="chat-card-title">NEW FOUNDER CHAT</span>
                        </div>
                        <div class="chat-card-body">
                            <div class="chat-card-prompt">How can we help you today?</div>
                            <div class="chat-card-input-row">
                                <span class="icon-circle">＋</span>
                            </div>
                        </div>
                    </div>

                    <aside class="chat-panel" id="chatPanel" aria-label="Founder chat">
                        <div class="panel-topbar">
                            <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1 1 auto;overflow:hidden;">
                                <span class="panel-traffic">
                                    <span class="red" data-action="close" title="Close"></span>
                                    <span class="yellow" data-action="minimize" title="Minimize"></span>
                                    <span class="green" data-action="maximize" title="Maximize"></span>
                                </span>
                                <nav class="breadcrumb">
                                    <span class="crumb">Personal</span>
                                    <span class="sep">›</span>
                                    <span class="crumb-current">
                                        <span>▣</span>
                                        <span id="projectCrumbName">{{ $projectName }}</span>
                                    </span>
                                </nav>
                            </div>
                            <button type="button" class="rail-icon" id="closeChatBtn" aria-label="Close chat">×</button>
                        </div>

                        <div class="panel-stream" id="chatStream">
                            <div class="thinking">
                                <span class="thinking-dot"></span>
                                <span>Hatchers is thinking…</span>
                            </div>
                        </div>

                        <div class="panel-composer">
                            <div class="composer-box">
                                <textarea id="chatInput" placeholder="{{ $quickPrompt }}"></textarea>
                                <button class="composer-send" id="chatSendBtn" type="button" aria-label="Send">↑</button>
                            </div>
                            <div class="composer-foot">
                                <span class="icon-circle">＋</span>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>

        <div class="agent-overlay" id="toolsPanel">
            <div class="agent-panel">
                <button class="agent-close" type="button" aria-label="Close" id="closeToolsBtn">×</button>
                <h1 class="agent-heading">
                    <span class="agent-orb"></span>
                    <span>What are we achieving today?</span>
                </h1>
                <div class="agent-composer">
                    <div class="agent-composer-prompt">How can we help you today?</div>
                    <button class="agent-composer-add" type="button" aria-label="Add">＋</button>
                </div>
                <div class="agent-actions">
                    @foreach($toolLinks as $tool)
                        <a href="{{ $tool['href'] }}" class="agent-chip">{{ $tool['label'] }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (() => {
            const shell = document.getElementById('guidebookShell');
            if (!shell) return;

            const onboardingNeeded = shell.dataset.onboardingNeeded === '1';
            const onboardingEndpoint = shell.dataset.onboardingEndpoint;
            const assistantEndpoint = shell.dataset.assistantEndpoint;
            const chatFab = document.getElementById('chatFab');
            const chatCard = document.getElementById('chatCard');
            const chatPanel = document.getElementById('chatPanel');
            const closeChatBtn = document.getElementById('closeChatBtn');
            const chatStream = document.getElementById('chatStream');
            const chatInput = document.getElementById('chatInput');
            const chatSendBtn = document.getElementById('chatSendBtn');
            const openToolsBtn = document.getElementById('openToolsBtn');
            const railAiToolsBtn = document.getElementById('railAiToolsBtn');
            const sidepaneNewAgentBtn = document.getElementById('sidepaneNewAgentBtn');
            const toolsPanel = document.getElementById('toolsPanel');
            const closeToolsBtn = document.getElementById('closeToolsBtn');
            const newProjectBtn = document.getElementById('newProjectBtn');
            const openSidebarBtn = document.getElementById('openSidebarBtn');
            const closeSidebarBtn = document.getElementById('closeSidebarBtn');
            const sidepane = document.getElementById('sidepane');
            const leftRail = document.getElementById('leftRail');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            let chatState = 'closed';
            let onboarding = {
                answers: {},
                currentStep: onboardingNeeded ? 'q1' : 'freeform',
                processing: false,
            };

            const steps = {
                q1: {
                    prompt: 'What do you do, and who do you help?',
                    help: 'Say it simply, like you would explain it to a friend.',
                    next: 'q2',
                },
                q2: {
                    prompt: 'Who is the one person most likely to pay you right now?',
                    help: 'Describe the best customer to target first, not everyone eventually.',
                    next: 'q3',
                },
                q3: {
                    prompt: 'What problem do you solve for them, and what happens if they do not fix it?',
                    help: 'Focus on the real commercial pain, not just the topic.',
                    next: 'q4',
                },
                q4: {
                    prompt: 'What might they use instead of you, even if it is not a direct competitor?',
                    help: 'A platform, a freelancer, doing nothing, or another workaround all count.',
                    next: 'budget',
                    optional: true,
                },
                budget: {
                    prompt: 'Do you want to grow organically, or are you open to using a budget for paid acquisition?',
                    choices: [
                        { value: 'organic', label: 'Organic only' },
                        { value: 'paid', label: 'Open to paid' },
                        { value: 'unsure', label: 'Not sure yet' },
                    ],
                    next: 'time',
                },
                time: {
                    prompt: 'How much time can you realistically put into this each week?',
                    choices: [
                        { value: 'low', label: 'Less than 2 hours' },
                        { value: 'mid', label: '3 to 5 hours' },
                        { value: 'high', label: '5+ hours' },
                    ],
                    next: 'complete',
                },
            };

            function setChatState(state) {
                chatState = state;
                document.body.setAttribute('data-chat', state);
                chatCard.classList.toggle('is-open', state === 'card');
                chatPanel.classList.toggle('is-open', state === 'panel');
                chatFab.classList.toggle('is-active', state !== 'closed');
            }

            function setToolsOpen(open) {
                toolsPanel.classList.toggle('is-open', open);
            }

            function setSidebarOpen(open) {
                sidepane.classList.toggle('is-open', open);
                leftRail.classList.toggle('is-hidden', open);
            }

            function appendBubble(role, html) {
                const wrap = document.createElement('div');
                wrap.className = role === 'user' ? 'msg-row user' : 'msg-row ai';

                if (role === 'user') {
                    const bubble = document.createElement('div');
                    bubble.className = 'bubble-user';
                    bubble.innerHTML = html;
                    wrap.appendChild(bubble);
                } else {
                    const block = document.createElement('div');
                    block.className = 'ai-block';
                    const avatar = document.createElement('div');
                    avatar.className = 'ai-avatar';
                    const text = document.createElement('div');
                    text.className = 'ai-text';
                    text.innerHTML = html;
                    block.appendChild(avatar);
                    block.appendChild(text);
                    wrap.appendChild(block);
                }

                chatStream.appendChild(wrap);
                chatStream.scrollTop = chatStream.scrollHeight;
                return wrap;
            }

            function showChoiceStep(stepKey) {
                const step = steps[stepKey];
                appendBubble('ai', `<p><strong>${step.prompt}</strong></p>`);
                const wrap = document.createElement('div');
                wrap.className = 'msg-row ai';
                const block = document.createElement('div');
                block.className = 'ai-block';
                const avatar = document.createElement('div');
                avatar.className = 'ai-avatar';
                const bubble = document.createElement('div');
                bubble.className = 'ai-text';
                const list = document.createElement('div');
                list.className = 'choice-list';

                step.choices.forEach((choice) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'choice';
                    button.textContent = choice.label;
                    button.addEventListener('click', () => {
                        if (onboarding.processing) return;
                        onboarding.answers[stepKey === 'budget' ? 'budget_strategy' : 'time_commitment'] = choice.value;
                        appendBubble('user', choice.label);
                        wrap.remove();
                        moveToStep(step.next);
                    });
                    list.appendChild(button);
                });

                bubble.appendChild(list);
                block.appendChild(avatar);
                block.appendChild(bubble);
                wrap.appendChild(block);
                chatStream.appendChild(wrap);
                chatStream.scrollTop = chatStream.scrollHeight;
            }

            function askFreeformStep(stepKey) {
                const step = steps[stepKey];
                const help = step.help ? `<p style="color: var(--text-muted); margin-top: 6px;">${step.help}</p>` : '';
                const wrap = appendBubble('ai', `<p><strong>${step.prompt}</strong></p>${help}`);
                if (step.optional) {
                    const actions = document.createElement('div');
                    actions.className = 'chat-actions';
                    const skipButton = document.createElement('button');
                    skipButton.type = 'button';
                    skipButton.className = 'choice';
                    skipButton.textContent = 'Skip this question';
                    skipButton.addEventListener('click', () => {
                        onboarding.answers[stepKey] = '';
                        appendBubble('user', '(Skipped)');
                        wrap.remove();
                        moveToStep(step.next);
                    });
                    actions.appendChild(skipButton);
                    wrap.querySelector('.ai-text')?.appendChild(actions);
                }
                chatInput.placeholder = step.optional ? 'You can answer or skip this one…' : 'Type your answer…';
                chatInput.focus();
                onboarding.currentStep = stepKey;
            }

            function moveToStep(stepKey) {
                if (stepKey === 'complete') {
                    submitOnboarding();
                    return;
                }

                if (steps[stepKey]?.choices) {
                    onboarding.currentStep = stepKey;
                    showChoiceStep(stepKey);
                    return;
                }

                askFreeformStep(stepKey);
            }

            async function submitOnboarding() {
                onboarding.processing = true;
                appendBubble('ai', '<p><strong>Building your launch plan…</strong></p><p style="color: var(--text-muted);">Hatchers is deducing your company intelligence, choosing the launch path, and writing your first milestones and tasks.</p>');

                try {
                    const response = await fetch(onboardingEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify(onboarding.answers),
                    });

                    const payload = await response.json();
                    if (!response.ok || !payload.success) {
                        throw new Error(payload.error || 'Hatchers could not finish the onboarding flow.');
                    }

                    appendBubble('ai', `<p><strong>Launch plan ready.</strong></p><p style="color: var(--text-muted);">${payload.reply}</p>`);
                    setTimeout(() => window.location.reload(), 1200);
                } catch (error) {
                    onboarding.processing = false;
                    appendBubble('ai', `<p><strong>We hit a setup problem.</strong></p><p style="color: var(--text-muted);">${error.message}</p>`);
                }
            }

            async function sendFreeformChat() {
                if (onboarding.processing) return;
                const value = chatInput.value.trim();
                if (!value) return;
                chatInput.value = '';

                if (onboardingNeeded && steps[onboarding.currentStep]) {
                    onboarding.answers[onboarding.currentStep] = value;
                    appendBubble('user', value);
                    moveToStep(steps[onboarding.currentStep].next);
                    return;
                }

                appendBubble('user', value);
                try {
                    const response = await fetch(assistantEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({
                            message: value,
                            current_page: 'prototype_dashboard',
                        }),
                    });
                    const payload = await response.json();
                    if (!response.ok || !payload.success) {
                        throw new Error(payload.error || 'Hatchers could not respond right now.');
                    }
                    appendBubble('ai', `<p>${payload.reply}</p>`);
                } catch (error) {
                    appendBubble('ai', `<p><strong>We could not answer that right now.</strong></p><p style="color: var(--text-muted);">${error.message}</p>`);
                }
            }

            function startOnboardingFlow() {
                setChatState('panel');
                chatStream.innerHTML = '';
                onboarding.answers = {};
                onboarding.currentStep = 'q1';
                onboarding.processing = false;
                appendBubble('ai', '<p><strong>Let’s build your launch plan.</strong></p><p style="color: var(--text-muted);">I will ask a few focused questions, infer the rest, and turn that into milestones, tasks, and the right website path.</p>');
                askFreeformStep('q1');
            }

            chatFab?.addEventListener('click', () => {
                if (chatState === 'closed') setChatState('card');
                else if (chatState === 'card') setChatState('panel');
                else setChatState('closed');
            });

            chatCard?.addEventListener('click', () => setChatState('panel'));
            closeChatBtn?.addEventListener('click', () => setChatState('closed'));
            openToolsBtn?.addEventListener('click', () => setToolsOpen(true));
            railAiToolsBtn?.addEventListener('click', () => setToolsOpen(true));
            sidepaneNewAgentBtn?.addEventListener('click', () => setToolsOpen(true));
            closeToolsBtn?.addEventListener('click', () => setToolsOpen(false));
            toolsPanel?.addEventListener('click', (event) => {
                if (event.target === toolsPanel) setToolsOpen(false);
            });
            openSidebarBtn?.addEventListener('click', () => setSidebarOpen(true));
            closeSidebarBtn?.addEventListener('click', () => setSidebarOpen(false));
            newProjectBtn?.addEventListener('click', startOnboardingFlow);
            chatSendBtn?.addEventListener('click', sendFreeformChat);
            chatInput?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    sendFreeformChat();
                }
            });

            if (onboardingNeeded) {
                setTimeout(() => {
                    setChatState('card');
                    startOnboardingFlow();
                }, 280);
            } else {
                chatStream.innerHTML = '';
                appendBubble('ai', '<p><strong>Your launch workspace is live.</strong></p><p style="color: var(--text-muted);">Ask Hatchers to refine the offer, improve the website, or break the next milestone into clearer tasks.</p>');
            }
        })();
    </script>
@endsection
