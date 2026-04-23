<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Hatchers OS' }}</title>
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
</head>
<body>
    @php
        $authUser = auth()->user();
        $hideTopbar = trim($__env->yieldContent('hide_topbar')) === '1';
        $dashboardLabel = 'Dashboard';
        if ($authUser?->role === 'admin') {
            $dashboardLabel = 'Admin';
        } elseif ($authUser?->role === 'mentor') {
            $dashboardLabel = 'Mentor';
        } elseif ($authUser?->role === 'founder') {
            $dashboardLabel = 'Founder';
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
                    <a class="top-link" href="/plans">Plans</a>
                    @auth
                        <a class="top-link" href="/dashboard">{{ $dashboardLabel }}</a>
                        @if ($authUser->role === 'founder')
                            <a class="top-link" href="/website">Website</a>
                        @endif
                        <span class="top-link" style="pointer-events: none;">{{ ucfirst($authUser->role) }}</span>
                    @endauth
                    @auth
                        <form method="POST" action="/logout" style="margin: 0;">
                            @csrf
                            <button class="top-link" type="submit" style="cursor: pointer;">Logout</button>
                        </form>
                    @else
                        <a class="top-link" href="/login">Login</a>
                    @endauth
                </nav>
            </header>
        @endunless

        <main class="page @yield('page_class')">
            @yield('content')
        </main>
    </div>
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
                if (status) status.textContent = 'Atlas is thinking...';
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
                        addBubble('atlas', data.error || 'Atlas could not respond right now.');
                        if (status) status.textContent = 'Atlas is temporarily unavailable.';
                    } else {
                        addBubble('atlas', data.reply || 'Atlas is here.', data.actions || []);
                        if (data.refresh) {
                            if (status) status.textContent = 'Atlas updated your workspace. Refreshing summary...';
                            window.setTimeout(() => window.location.reload(), 900);
                        } else if (status) {
                            status.textContent = 'Atlas is synced with your OS context.';
                        }
                    }
                } catch (error) {
                    addBubble('atlas', 'Atlas could not respond right now.');
                    if (status) status.textContent = 'Connection issue. Please try again.';
                } finally {
                    if (sendButton) sendButton.disabled = false;
                }
            });

            setOpen(false);
        })();
    </script>
    @yield('scripts')
</body>
</html>
