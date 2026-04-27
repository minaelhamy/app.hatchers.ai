<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Hatchers OS' }}</title>
    @php
        $osEmbedMode = request()->boolean('os_embed');
    @endphp
    <style>
        :root {
            --bg: #f6f1e8;
            --bg-strong: #f0e7da;
            --panel: rgba(255, 252, 247, 0.92);
            --panel-solid: #fffdf8;
            --line: #dccfbf;
            --ink: #141414;
            --muted: #6a6259;
            --accent: #111111;
            --accent-soft: #d8c7ab;
            --rose: #b32253;
            --success: #2c7a57;
            --warning: #9a6b1b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Avenir Next", "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(216, 199, 171, 0.35), transparent 22%),
                linear-gradient(180deg, #fcf8f1 0%, var(--bg) 100%);
        }

        a { color: inherit; }

        .shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 22px 32px;
            border-bottom: 1px solid var(--line);
            background: rgba(255, 252, 247, 0.72);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .brand {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo {
            height: 38px;
            width: auto;
            display: block;
        }

        .brand-copy {
            display: grid;
            gap: 2px;
            line-height: 1.05;
        }

        .brand-title {
            font-size: 1.16rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .brand-subtitle {
            font-size: 0.78rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .top-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .top-link {
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: var(--panel-solid);
            font-size: 0.92rem;
        }

        .page {
            padding: 30px 32px 48px;
        }

        .public-shell {
            max-width: 1180px;
            margin: 0 auto;
            width: 100%;
        }

        .public-shell.narrow {
            max-width: 860px;
        }

        .hero {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 28px;
            padding: 28px;
            box-shadow: 0 18px 50px rgba(52, 41, 26, 0.08);
            margin-bottom: 22px;
        }

        .muted {
            color: var(--muted);
        }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.16em;
            font-size: 0.72rem;
            color: var(--muted);
            margin-bottom: 10px;
        }

        h1, h2, h3, p {
            margin: 0;
        }

        h1 {
            font-size: clamp(2rem, 4vw, 3.6rem);
            line-height: 1.04;
            margin-bottom: 12px;
        }

        h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .section {
            display: grid;
            gap: 18px;
            margin-bottom: 22px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .card {
            background: var(--panel-solid);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 22px;
            box-shadow: 0 18px 45px rgba(52, 41, 26, 0.06);
        }

        .cta-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            padding: 12px 16px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: var(--panel-solid);
            font-weight: 600;
        }

        .btn.primary {
            background: var(--ink);
            border-color: var(--ink);
            color: #fff;
        }

        .pill {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: var(--bg-strong);
            font-size: 0.88rem;
        }

        .stack {
            display: grid;
            gap: 12px;
        }

        .stack-item {
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 16px;
            padding: 14px 16px;
        }

        .sidebar-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 22px;
        }

        .sidebar-card {
            background: rgba(255, 252, 247, 0.85);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 20px;
            height: fit-content;
            position: sticky;
            top: 104px;
        }

        .nav-group {
            margin-bottom: 18px;
        }

        .nav-group-title {
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--muted);
            margin: 8px 10px;
        }

        .nav-item {
            display: block;
            padding: 12px 14px;
            border-radius: 14px;
            text-decoration: none;
            margin-bottom: 6px;
        }

        .nav-item.active {
            background: var(--ink);
            color: white;
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .metric strong {
            display: block;
            font-size: 1.8rem;
            margin-top: 6px;
        }

        .plan-card {
            padding: 22px;
            border-radius: 26px;
            background: var(--panel-solid);
            border: 1px solid var(--line);
        }

        .price {
            font-size: 2.4rem;
            font-weight: 700;
            margin: 10px 0 6px;
        }

        .assistant {
            position: fixed;
            right: 28px;
            bottom: 24px;
            width: 310px;
            background: rgba(18, 18, 18, 0.96);
            color: #fff;
            border-radius: 24px;
            padding: 18px;
            box-shadow: 0 28px 70px rgba(0, 0, 0, 0.28);
        }

        .assistant-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }

        .assistant-toggle {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            cursor: pointer;
            font-weight: 700;
        }

        .assistant-body {
            display: none;
            gap: 12px;
        }

        .assistant.open .assistant-body {
            display: grid;
        }

        .assistant-feed {
            display: grid;
            gap: 10px;
            max-height: 280px;
            overflow-y: auto;
            padding-right: 2px;
        }

        .assistant-bubble {
            border-radius: 16px;
            padding: 12px 14px;
            font-size: 0.93rem;
            line-height: 1.45;
        }

        .assistant-bubble.user {
            background: rgba(255, 255, 255, 0.12);
        }

        .assistant-bubble.atlas {
            background: rgba(216, 199, 171, 0.18);
        }

        .assistant-form {
            display: grid;
            gap: 10px;
        }

        .assistant-textarea {
            width: 100%;
            min-height: 88px;
            resize: vertical;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            padding: 12px 14px;
            font: inherit;
        }

        .assistant-textarea::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .assistant-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .assistant-status {
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.68);
        }

        .assistant-send {
            border: 0;
            border-radius: 999px;
            padding: 11px 16px;
            background: #f2e7d5;
            color: #111;
            font-weight: 700;
            cursor: pointer;
        }

        .assistant-chip {
            display: inline-block;
            margin-top: 8px;
            margin-right: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.84);
            font-size: 0.78rem;
        }

        .assistant p {
            color: rgba(255, 255, 255, 0.76);
            line-height: 1.45;
        }

        @media (max-width: 1180px) {
            .grid-2, .grid-3, .metrics, .sidebar-layout {
                grid-template-columns: 1fr;
            }

            .sidebar-card {
                position: static;
            }
        }

        @media (max-width: 840px) {
            .topbar, .page {
                padding-left: 18px;
                padding-right: 18px;
            }

            .assistant {
                position: static;
                width: auto;
                margin-top: 22px;
            }
        }
    </style>
    @yield('head')
    <style>
        body.os-founder-body {
            overflow-x: hidden;
        }

        .os-window-host {
            position: absolute;
            inset: 92px 64px 56px 64px;
            pointer-events: none;
            z-index: 8;
        }

        .os-app-window {
            position: absolute;
            border-radius: 28px;
            overflow: hidden;
            border: 1px solid rgba(206, 194, 180, 0.72);
            background: rgba(255, 251, 245, 0.96);
            box-shadow:
                0 28px 90px rgba(55, 41, 24, 0.18),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            pointer-events: auto;
            min-width: 360px;
            min-height: 280px;
        }

        .os-app-window.active {
            box-shadow:
                0 34px 110px rgba(55, 41, 24, 0.24),
                inset 0 1px 0 rgba(255, 255, 255, 0.92);
        }

        .os-app-window-bar,
        .os-inline-window-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 18px;
            background: rgba(255, 250, 245, 0.92);
            border-bottom: 1px solid rgba(214, 201, 184, 0.68);
        }

        .os-app-window-dots,
        .os-inline-window-dots {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .os-app-window-dots span,
        .os-inline-window-dots span {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            display: inline-block;
        }

        .os-app-window-dots span:nth-child(1),
        .os-inline-window-dots span:nth-child(1) { background: #ff7965; }
        .os-app-window-dots span:nth-child(2),
        .os-inline-window-dots span:nth-child(2) { background: #f6c85e; }
        .os-app-window-dots span:nth-child(3),
        .os-inline-window-dots span:nth-child(3) { background: #68c06a; }

        .os-app-window-title,
        .os-inline-window-title {
            flex: 1;
            min-width: 0;
            text-align: center;
            font-size: 0.92rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .os-app-window-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .os-app-window-close {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            border: 1px solid rgba(214, 201, 184, 0.9);
            background: rgba(255, 255, 255, 0.82);
            cursor: pointer;
            font: inherit;
        }

        .os-app-window-frame {
            width: 100%;
            height: calc(100% - 61px);
            border: 0;
            background: #fffdf8;
            display: block;
        }

        .os-inline-window-bar {
            margin: -24px -24px 20px;
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
        }

        body.os-embed-mode .shell,
        body.os-embed-mode .page.founder-home-page,
        body.os-embed-mode .founder-home,
        body.os-embed-mode .workspace-shell,
        body.os-embed-mode .tracker-shell,
        body.os-embed-mode .marketing-shell,
        body.os-embed-mode .settings-shell,
        body.os-embed-mode .learning-shell,
        body.os-embed-mode .notifications-shell,
        body.os-embed-mode .tools-shell,
        body.os-embed-mode .media-shell,
        body.os-embed-mode .activity-shell,
        body.os-embed-mode .wallet-shell,
        body.os-embed-mode .commerce-shell,
        body.os-embed-mode .ops-shell,
        body.os-embed-mode .tasks-shell,
        body.os-embed-mode .analytics-shell,
        body.os-embed-mode .atlas-frame-shell {
            min-height: 100vh;
            background: transparent !important;
        }

        body.os-embed-mode .founder-sidebar,
        body.os-embed-mode .workspace-sidebar,
        body.os-embed-mode .tracker-sidebar,
        body.os-embed-mode .marketing-sidebar,
        body.os-embed-mode .settings-sidebar,
        body.os-embed-mode .learning-sidebar,
        body.os-embed-mode .notifications-sidebar,
        body.os-embed-mode .tools-sidebar,
        body.os-embed-mode .media-sidebar,
        body.os-embed-mode .activity-sidebar,
        body.os-embed-mode .wallet-sidebar,
        body.os-embed-mode .commerce-sidebar,
        body.os-embed-mode .ops-sidebar,
        body.os-embed-mode .tasks-sidebar,
        body.os-embed-mode .analytics-sidebar,
        body.os-embed-mode .atlas-frame-sidebar,
        body.os-embed-mode .founder-rightbar,
        body.os-embed-mode .workspace-rightbar,
        body.os-embed-mode .tracker-rightbar,
        body.os-embed-mode .marketing-rightbar,
        body.os-embed-mode .settings-rightbar,
        body.os-embed-mode .learning-rightbar,
        body.os-embed-mode .notifications-rightbar,
        body.os-embed-mode .tools-rightbar,
        body.os-embed-mode .media-rightbar,
        body.os-embed-mode .activity-rightbar,
        body.os-embed-mode .wallet-rightbar,
        body.os-embed-mode .commerce-rightbar,
        body.os-embed-mode .ops-rightbar,
        body.os-embed-mode .tasks-rightbar,
        body.os-embed-mode .analytics-rightbar {
            display: none !important;
        }

        body.os-embed-mode .founder-home,
        body.os-embed-mode .workspace-shell,
        body.os-embed-mode .tracker-shell,
        body.os-embed-mode .marketing-shell,
        body.os-embed-mode .settings-shell,
        body.os-embed-mode .learning-shell,
        body.os-embed-mode .notifications-shell,
        body.os-embed-mode .tools-shell,
        body.os-embed-mode .media-shell,
        body.os-embed-mode .activity-shell,
        body.os-embed-mode .wallet-shell,
        body.os-embed-mode .commerce-shell,
        body.os-embed-mode .ops-shell,
        body.os-embed-mode .tasks-shell,
        body.os-embed-mode .analytics-shell,
        body.os-embed-mode .atlas-frame-shell {
            grid-template-columns: 1fr !important;
        }

        body.os-embed-mode .founder-main,
        body.os-embed-mode .workspace-main,
        body.os-embed-mode .tracker-main,
        body.os-embed-mode .marketing-main,
        body.os-embed-mode .settings-main,
        body.os-embed-mode .learning-main,
        body.os-embed-mode .notifications-main,
        body.os-embed-mode .tools-main,
        body.os-embed-mode .media-main,
        body.os-embed-mode .activity-main,
        body.os-embed-mode .wallet-main,
        body.os-embed-mode .commerce-main,
        body.os-embed-mode .ops-main,
        body.os-embed-mode .tasks-main,
        body.os-embed-mode .analytics-main,
        body.os-embed-mode .atlas-frame-main {
            padding: 12px !important;
        }

        body.os-embed-mode .founder-main-inner,
        body.os-embed-mode .workspace-main-inner,
        body.os-embed-mode .tracker-main-inner,
        body.os-embed-mode .marketing-main-inner,
        body.os-embed-mode .settings-main-inner,
        body.os-embed-mode .learning-main-inner,
        body.os-embed-mode .notifications-main-inner,
        body.os-embed-mode .tools-main-inner,
        body.os-embed-mode .media-main-inner,
        body.os-embed-mode .activity-main-inner,
        body.os-embed-mode .wallet-main-inner,
        body.os-embed-mode .commerce-main-inner,
        body.os-embed-mode .ops-main-inner,
        body.os-embed-mode .tasks-main-inner,
        body.os-embed-mode .analytics-main-inner,
        body.os-embed-mode .atlas-frame-main-inner {
            min-height: calc(100vh - 24px);
            border-radius: 24px;
            padding: 24px 24px 28px !important;
            background: rgba(255, 251, 245, 0.96) !important;
            border: 1px solid rgba(214, 201, 184, 0.72) !important;
            box-shadow: 0 18px 60px rgba(71, 52, 31, 0.08) !important;
        }

        body.os-embed-mode .assistant {
            display: none !important;
        }

        .os-boot-screen {
            position: fixed;
            inset: 0;
            display: grid;
            place-items: center;
            background: linear-gradient(165deg, #c8b8b0 0%, #e8d5cc 100%);
            z-index: 9999;
            transition: opacity 0.75s ease, visibility 0.75s ease;
        }

        .os-boot-screen.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .os-boot-inner {
            display: grid;
            justify-items: center;
            gap: 22px;
        }

        .os-boot-mark {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: linear-gradient(135deg,#e11d74,#ef4444);
            box-shadow: 0 16px 40px rgba(225,29,116,0.3);
        }

        .os-boot-title {
            font-size: 16px;
            letter-spacing: 0.2em;
            color: rgba(40,30,30,0.48);
            text-transform: uppercase;
            font-weight: 600;
        }

        .os-boot-copy {
            margin-top: 6px;
            font-size: 12px;
            color: rgba(40,30,30,0.45);
            letter-spacing: 0.08em;
        }

        @media (max-width: 900px) {
            .os-window-host {
                inset: 88px 18px 28px 18px;
            }
        }
    </style>
</head>
<body class="{{ $osEmbedMode ? 'os-embed-mode ' : '' }}{{ trim($__env->yieldContent('page_class')) === 'founder-home-page' ? 'os-founder-body' : '' }}">
    @php
        $authUser = auth()->user();
        $hideTopbar = trim($__env->yieldContent('hide_topbar')) === '1';
        $dashboardLabel = 'Dashboard';
        if ($authUser?->role === 'admin') {
            $dashboardLabel = 'Admin';
        } elseif ($authUser?->role === 'mentor') {
            $dashboardLabel = 'Mentor';
        }
    @endphp
    <div class="shell">
        @unless($hideTopbar)
            <header class="topbar">
                <a href="/" class="brand">
                    <img class="brand-logo" src="/brand/hatchers-ai-logo.png" alt="Hatchers AI">
                    <span class="brand-copy">
                        <span class="brand-title">Hatchers Ai Business OS</span>
                        <span class="brand-subtitle">Founder Operating System</span>
                    </span>
                </a>
                <nav class="top-links">
                    @auth
                        <a class="top-link" href="/dashboard">{{ $dashboardLabel }}</a>
                    @endauth
                    @auth
                        <form method="POST" action="/logout" style="margin: 0;">
                            @csrf
                            <button class="top-link" type="submit" style="cursor: pointer;">Logout</button>
                        </form>
                    @else
                        <a class="top-link" href="/plans">Plans</a>
                        <a class="top-link" href="/login">Login</a>
                    @endauth
                </nav>
            </header>
        @endunless

        <main class="page @yield('page_class')">
            @yield('content')
        </main>
    </div>
    @if (trim($__env->yieldContent('page_class')) === 'founder-home-page' && !$osEmbedMode)
        <div class="os-boot-screen" data-os-boot>
            <div class="os-boot-inner">
                <div class="os-boot-mark"></div>
                <div class="os-boot-title">Hatchers OS</div>
                <div class="os-boot-copy">Loading your workspace…</div>
            </div>
        </div>
    @endif
    @yield('assistant')
    <script>
        (() => {
            const assistant = document.querySelector('[data-os-assistant]');
            if (!assistant) return;

            const toggle = assistant.querySelector('[data-assistant-toggle]');
            const form = assistant.querySelector('[data-assistant-form]');
            const textarea = assistant.querySelector('[data-assistant-input]');
            const feed = assistant.querySelector('[data-assistant-feed]');
            const status = assistant.querySelector('[data-assistant-status]');
            const sendButton = assistant.querySelector('[data-assistant-send]');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const setOpen = (open) => {
                assistant.classList.toggle('open', open);
                if (toggle) toggle.textContent = open ? '−' : '+';
            };

            const addBubble = (type, text, actions = []) => {
                const bubble = document.createElement('div');
                bubble.className = `assistant-bubble ${type}`;

                const content = document.createElement('div');
                content.textContent = text;
                bubble.appendChild(content);

                if (Array.isArray(actions) && actions.length) {
                    actions.slice(0, 3).forEach((action) => {
                        const label = action.title || action.cta || action.platform || '';
                        if (!label) return;
                        const chip = document.createElement('span');
                        chip.className = 'assistant-chip';
                        chip.textContent = label;
                        bubble.appendChild(chip);
                    });
                }

                feed?.appendChild(bubble);
                if (feed) feed.scrollTop = feed.scrollHeight;
            };

            toggle?.addEventListener('click', () => {
                setOpen(!assistant.classList.contains('open'));
            });

            form?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const message = textarea?.value.trim();
                if (!message) return;

                addBubble('user', message);
                textarea.value = '';
                if (status) status.textContent = 'Hatchers AI is thinking...';
                if (sendButton) sendButton.disabled = true;

                try {
                    const response = await fetch('/assistant/chat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            message,
                            current_page: window.location.pathname.replace(/^\//, '') || 'dashboard',
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        addBubble('atlas', data.error || 'Hatchers AI could not respond right now.');
                        if (status) status.textContent = 'Hatchers AI is temporarily unavailable.';
                    } else {
                        addBubble('atlas', data.reply || 'Hatchers AI is here.', data.actions || []);
                        if (data.refresh) {
                            if (status) status.textContent = 'Hatchers AI updated your workspace. Refreshing summary...';
                            window.setTimeout(() => window.location.reload(), 900);
                        } else if (status) {
                            status.textContent = 'Hatchers AI is synced with your OS context.';
                        }
                    }
                } catch (error) {
                    addBubble('atlas', 'Hatchers AI could not respond right now.');
                    if (status) status.textContent = 'Connection issue. Please try again.';
                } finally {
                    if (sendButton) sendButton.disabled = false;
                }
            });

            setOpen(false);
        })();
    </script>
    <script>
        (() => {
            const launchers = document.querySelectorAll('[data-os-launcher]');
            if (!launchers.length || typeof window.localStorage === 'undefined') return;

            launchers.forEach((launcher) => {
                const storageKey = launcher.dataset.storageKey || 'hatchers-os-launcher-order';
                const selector = '[data-launcher-key]';
                let dragged = null;
                let moved = false;

                const items = () => Array.from(launcher.querySelectorAll(selector));

                const persistOrder = () => {
                    const order = items().map((item) => item.dataset.launcherKey).filter(Boolean);
                    window.localStorage.setItem(storageKey, JSON.stringify(order));
                };

                const applyStoredOrder = () => {
                    const raw = window.localStorage.getItem(storageKey);
                    if (!raw) return;

                    try {
                        const order = JSON.parse(raw);
                        if (!Array.isArray(order)) return;

                        order.forEach((key) => {
                            const match = launcher.querySelector(`${selector}[data-launcher-key="${key}"]`);
                            if (match) launcher.appendChild(match);
                        });
                    } catch (error) {
                        window.localStorage.removeItem(storageKey);
                    }
                };

                const clearDropTargets = () => {
                    items().forEach((item) => item.classList.remove('drop-target'));
                };

                applyStoredOrder();

                items().forEach((item) => {
                    item.addEventListener('dragstart', (event) => {
                        dragged = item;
                        moved = false;
                        item.classList.add('dragging');
                        if (event.dataTransfer) {
                            event.dataTransfer.effectAllowed = 'move';
                            event.dataTransfer.setData('text/plain', item.dataset.launcherKey || '');
                        }
                    });

                    item.addEventListener('dragend', () => {
                        item.classList.remove('dragging');
                        clearDropTargets();
                        window.setTimeout(() => {
                            if (moved) item.dataset.dragMoved = '1';
                            moved = false;
                        }, 0);
                    });

                    item.addEventListener('dragover', (event) => {
                        event.preventDefault();
                        if (!dragged || dragged === item) return;
                        clearDropTargets();
                        item.classList.add('drop-target');
                    });

                    item.addEventListener('dragleave', () => {
                        item.classList.remove('drop-target');
                    });

                    item.addEventListener('drop', (event) => {
                        event.preventDefault();
                        if (!dragged || dragged === item) return;

                        const rect = item.getBoundingClientRect();
                        const offset = event.clientY - rect.top;
                        const shouldInsertAfter = offset > rect.height / 2;

                        if (shouldInsertAfter) {
                            launcher.insertBefore(dragged, item.nextSibling);
                        } else {
                            launcher.insertBefore(dragged, item);
                        }

                        clearDropTargets();
                        moved = true;
                        persistOrder();
                    });

                    item.addEventListener('click', (event) => {
                        if (item.dataset.dragMoved === '1') {
                            event.preventDefault();
                            item.dataset.dragMoved = '0';
                        }
                    });
                });
            });
        })();
    </script>
    <script>
        (() => {
            const boot = document.querySelector('[data-os-boot]');
            if (!boot || typeof window.sessionStorage === 'undefined') return;

            const storageKey = 'hatchers-os-boot-seen';
            if (window.sessionStorage.getItem(storageKey) === '1') {
                boot.classList.add('hidden');
                return;
            }

            window.setTimeout(() => {
                boot.classList.add('hidden');
                window.sessionStorage.setItem(storageKey, '1');
            }, 1800);
        })();
    </script>
    <script>
        (() => {
            const page = document.querySelector('.page.founder-home-page');
            if (!page) return;

            const selectors = [
                '.founder-main-inner',
                '.workspace-main-inner',
                '.tracker-main-inner',
                '.marketing-main-inner',
                '.settings-main-inner',
                '.learning-main-inner',
                '.notifications-main-inner',
                '.tools-main-inner',
                '.media-main-inner',
                '.activity-main-inner',
                '.wallet-main-inner',
                '.commerce-main-inner',
                '.ops-main-inner',
                '.tasks-main-inner',
                '.analytics-main-inner',
                '.atlas-frame-main-inner'
            ];

            selectors.forEach((selector) => {
                page.querySelectorAll(selector).forEach((panel) => {
                    if (panel.querySelector('.os-inline-window-bar')) return;
                    const heading = panel.querySelector('h1, h2');
                    const title = heading?.textContent?.trim() || 'Workspace';
                    const bar = document.createElement('div');
                    bar.className = 'os-inline-window-bar';
                    bar.innerHTML = `
                        <div class="os-inline-window-dots"><span></span><span></span><span></span></div>
                        <div class="os-inline-window-title">${title}</div>
                        <div style="width:34px;"></div>
                    `;
                    panel.prepend(bar);
                });
            });
        })();
    </script>
    <script>
        (() => {
            const desktop = document.querySelector('[data-os-desktop-home]');
            const host = document.querySelector('[data-os-window-host]');
            if (!desktop || !host) return;

            const launcherNodes = Array.from(document.querySelectorAll('[data-launcher-route]'));
            const routeMap = {};
            launcherNodes.forEach((node) => {
                const key = node.dataset.launcherKey;
                if (!key || routeMap[key]) return;
                routeMap[key] = {
                    key,
                    label: node.dataset.launcherLabel || key,
                    route: node.dataset.launcherRoute || '/',
                    icon: node.dataset.launcherIcon || key.slice(0, 2).toUpperCase(),
                };
            });

            let zIndex = 20;
            const windows = new Map();

            const withEmbedParam = (url) => {
                try {
                    const target = new URL(url, window.location.origin);
                    target.searchParams.set('os_embed', '1');
                    return `${target.pathname}${target.search}${target.hash}`;
                } catch (error) {
                    return url.includes('?') ? `${url}&os_embed=1` : `${url}?os_embed=1`;
                }
            };

            const focusWindow = (key) => {
                const active = windows.get(key);
                if (!active) return;
                windows.forEach((entry) => entry.el.classList.remove('active'));
                zIndex += 1;
                active.el.style.zIndex = String(zIndex);
                active.el.classList.add('active');
            };

            const buildWindow = (app, startX, startY) => {
                const win = document.createElement('section');
                win.className = 'os-app-window active';
                win.dataset.windowKey = app.key;
                win.style.left = `${startX}px`;
                win.style.top = `${startY}px`;
                win.style.width = '560px';
                win.style.height = '520px';
                win.style.zIndex = String(++zIndex);
                win.innerHTML = `
                    <div class="os-app-window-bar" data-window-drag>
                        <div class="os-app-window-dots"><span></span><span></span><span></span></div>
                        <div class="os-app-window-title">${app.label}</div>
                        <div class="os-app-window-actions">
                            <button class="os-app-window-close" type="button" aria-label="Close ${app.label}">×</button>
                        </div>
                    </div>
                    <iframe class="os-app-window-frame" title="${app.label}" src="${withEmbedParam(app.route)}"></iframe>
                `;
                host.appendChild(win);

                const close = () => {
                    win.remove();
                    windows.delete(app.key);
                };

                win.querySelector('.os-app-window-close')?.addEventListener('click', close);
                win.addEventListener('mousedown', () => focusWindow(app.key));

                const dragHandle = win.querySelector('[data-window-drag]');
                let dragging = false;
                let originX = 0;
                let originY = 0;
                let baseX = 0;
                let baseY = 0;

                dragHandle?.addEventListener('mousedown', (event) => {
                    dragging = true;
                    focusWindow(app.key);
                    originX = event.clientX;
                    originY = event.clientY;
                    baseX = parseFloat(win.style.left || '0');
                    baseY = parseFloat(win.style.top || '0');
                    document.body.style.userSelect = 'none';
                    event.preventDefault();
                });

                window.addEventListener('mousemove', (event) => {
                    if (!dragging) return;
                    const nextX = baseX + (event.clientX - originX);
                    const nextY = baseY + (event.clientY - originY);
                    win.style.left = `${Math.max(16, nextX)}px`;
                    win.style.top = `${Math.max(16, nextY)}px`;
                });

                window.addEventListener('mouseup', () => {
                    dragging = false;
                    document.body.style.userSelect = '';
                });

                windows.set(app.key, { el: win, close });
                focusWindow(app.key);
            };

            const openApp = (key) => {
                const app = routeMap[key];
                if (!app) return;
                if (windows.has(key)) {
                    focusWindow(key);
                    return;
                }

                const count = windows.size;
                buildWindow(app, 72 + (count * 42), 88 + (count * 34));
            };

            launcherNodes.forEach((node) => {
                node.addEventListener('click', (event) => {
                    if (node.dataset.dragMoved === '1') return;
                    event.preventDefault();
                    const key = node.dataset.launcherKey;
                    if (key) openApp(key);
                });

                node.addEventListener('contextmenu', (event) => {
                    if (!node.classList.contains('os-dock-item')) return;
                    event.preventDefault();
                    const key = node.dataset.launcherKey;
                    const active = key ? windows.get(key) : null;
                    if (active) active.close();
                });
            });

            const initialKey = desktop.dataset.osOpen;
            if (initialKey && routeMap[initialKey]) {
                window.setTimeout(() => openApp(initialKey), 220);
            }
        })();
    </script>
    @yield('scripts')
</body>
</html>
