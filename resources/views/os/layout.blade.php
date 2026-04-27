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
            top: 36px;
            right: 34px;
            bottom: 34px;
            width: 420px;
            display: grid;
            grid-template-rows: auto minmax(0, 1fr);
            gap: 12px;
            padding: 18px;
            border-radius: 34px;
            border: 1px solid rgba(235, 227, 218, 0.92);
            background:
                radial-gradient(circle at 100% 0%, rgba(233, 191, 201, 0.18), transparent 0 24%),
                linear-gradient(180deg, rgba(255, 252, 248, 0.96), rgba(250, 246, 240, 0.94));
            box-shadow:
                0 32px 84px rgba(58, 41, 25, 0.14),
                inset 0 1px 0 rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(22px);
            color: #1a1714;
            z-index: 40;
            overflow: hidden;
        }

        .assistant-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(227, 216, 203, 0.72);
        }

        .assistant-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            font-size: 0.7rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(116, 98, 86, 0.74);
        }

        .assistant-kicker-mark {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(135deg, #ef476f, #ee6c4d);
            box-shadow: 0 0 0 6px rgba(239, 71, 111, 0.08);
            flex-shrink: 0;
        }

        .assistant-title {
            font-family: "Inter Tight", "Inter", "Avenir Next", sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: -0.04em;
            line-height: 1;
            margin-bottom: 4px;
        }

        .assistant-subtitle {
            color: rgba(100, 84, 72, 0.8);
            font-size: 0.86rem;
            line-height: 1.42;
            max-width: 30ch;
        }

        .assistant-toggle {
            width: 38px;
            height: 38px;
            border-radius: 16px;
            border: 1px solid rgba(223, 211, 197, 0.92);
            background: rgba(255, 255, 255, 0.68);
            color: rgba(80, 63, 53, 0.86);
            cursor: pointer;
            font-weight: 700;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }

        .assistant-session {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 20px;
            border: 1px solid rgba(227, 216, 203, 0.95);
            background: rgba(255,255,255,0.64);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.84);
        }

        .assistant-session-label {
            min-width: 0;
        }

        .assistant-session-kicker {
            font-size: 0.7rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(122, 105, 92, 0.72);
            margin-bottom: 4px;
        }

        .assistant-session-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: #1d1713;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .assistant-session-reset {
            border: 1px solid rgba(220, 207, 191, 0.92);
            background: rgba(255, 253, 249, 0.9);
            color: rgba(94, 77, 67, 0.9);
            border-radius: 999px;
            padding: 8px 12px;
            font: inherit;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            flex-shrink: 0;
        }

        .assistant-thread-list {
            display: grid;
            gap: 8px;
            max-height: 150px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .assistant-thread-item {
            display: grid;
            gap: 4px;
            width: 100%;
            text-align: left;
            border: 1px solid rgba(227, 216, 203, 0.95);
            background: rgba(255,255,255,0.7);
            color: #211914;
            border-radius: 18px;
            padding: 11px 12px;
            font: inherit;
            cursor: pointer;
        }

        .assistant-thread-item.active {
            background: rgba(247, 241, 233, 0.96);
            border-color: rgba(208, 190, 172, 0.95);
        }

        .assistant-thread-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: #1d1713;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .assistant-thread-preview {
            font-size: 0.78rem;
            color: rgba(110, 92, 81, 0.8);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .assistant-body {
            display: grid;
            grid-template-rows: auto auto minmax(0, 1fr) auto;
            gap: 12px;
            min-height: 0;
            overflow: hidden;
        }

        .assistant.collapsed {
            grid-template-rows: auto;
            width: 286px;
            bottom: auto;
        }

        .assistant.collapsed .assistant-body {
            display: none;
        }

        .assistant-snapshot {
            display: grid;
            gap: 10px;
            padding: 14px;
            border-radius: 24px;
            border: 1px solid rgba(227, 216, 203, 0.95);
            background: linear-gradient(180deg, rgba(255,255,255,0.7), rgba(252,247,241,0.74));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.86);
        }

        .assistant-snapshot-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .assistant-snapshot-title {
            font-size: 0.76rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(122, 105, 92, 0.74);
        }

        .assistant-snapshot-focus {
            font-family: "Inter Tight", "Inter", "Avenir Next", sans-serif;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: #1b1512;
            margin-top: 4px;
        }

        .assistant-snapshot-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .assistant-stat {
            padding: 10px 11px;
            border-radius: 18px;
            border: 1px solid rgba(227, 216, 203, 0.95);
            background: rgba(255, 255, 255, 0.76);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.84);
        }

        .assistant-stat-label {
            display: block;
            font-size: 0.66rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(120, 102, 89, 0.7);
            margin-bottom: 4px;
        }

        .assistant-stat-value {
            display: block;
            font-family: "Inter Tight", "Inter", "Avenir Next", sans-serif;
            font-size: 0.98rem;
            font-weight: 700;
            letter-spacing: -0.04em;
            color: #191512;
        }

        .assistant-prompts,
        .assistant-composer {
            padding: 14px;
            border-radius: 24px;
            border: 1px solid rgba(227, 216, 203, 0.95);
            background: rgba(255, 255, 255, 0.62);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.82);
        }

        .assistant-prompts-title {
            font-size: 0.74rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(122, 105, 92, 0.74);
        }

        .assistant-prompts {
            display: grid;
            gap: 10px;
            max-height: 170px;
            overflow: hidden;
            min-height: 0;
            transition:
                max-height 220ms ease,
                opacity 180ms ease,
                padding 180ms ease,
                border-color 180ms ease,
                background 180ms ease;
        }

        .assistant.has-conversation .assistant-prompts {
            max-height: 56px;
            padding-top: 12px;
            padding-bottom: 12px;
            background: rgba(255, 255, 255, 0.54);
        }

        .assistant-prompts-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .assistant-prompts-toggle {
            border: 1px solid rgba(220, 207, 191, 0.92);
            background: rgba(255, 253, 249, 0.9);
            color: rgba(94, 77, 67, 0.88);
            border-radius: 999px;
            padding: 7px 11px;
            font: inherit;
            font-size: 0.76rem;
            font-weight: 600;
            cursor: pointer;
        }

        .assistant.has-conversation:not(.prompts-open) .assistant-prompt-list {
            display: none;
        }

        .assistant.has-conversation.prompts-open .assistant-prompts {
            max-height: 170px;
        }

        .assistant-prompt-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-height: 112px;
            overflow-y: auto;
            padding-right: 4px;
            align-content: flex-start;
        }

        .assistant-prompt {
            border: 1px solid rgba(220, 207, 191, 0.9);
            background: rgba(255, 253, 249, 0.9);
            color: #2a231e;
            border-radius: 999px;
            padding: 9px 12px;
            font: inherit;
            font-size: 0.82rem;
            cursor: pointer;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.74);
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .assistant-prompt:hover {
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 0.96);
        }

        .assistant-feed {
            display: grid;
            gap: 12px;
            min-height: 0;
            overflow-y: auto;
            padding: 6px 8px 2px 2px;
            align-content: start;
            overscroll-behavior: contain;
        }

        .assistant-bubble {
            max-width: 90%;
            border-radius: 24px;
            padding: 14px 16px;
            font-size: 0.94rem;
            line-height: 1.58;
            border: 1px solid rgba(223, 211, 198, 0.88);
            box-shadow:
                0 10px 20px rgba(79, 60, 39, 0.04),
                inset 0 1px 0 rgba(255,255,255,0.74);
        }

        .assistant-bubble.user {
            justify-self: end;
            background: linear-gradient(180deg, rgba(31, 27, 24, 0.94), rgba(41, 34, 31, 0.94));
            color: #fffaf5;
            border-color: rgba(31, 27, 24, 0.94);
        }

        .assistant-bubble.atlas {
            justify-self: start;
            background: rgba(255, 253, 249, 0.94);
            color: rgba(37, 30, 26, 0.9);
        }

        .assistant-bubble-title {
            display: block;
            margin-bottom: 6px;
            font-size: 0.74rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(123, 107, 95, 0.72);
        }

        .assistant-bubble.user .assistant-bubble-title {
            color: rgba(255, 243, 232, 0.62);
        }

        .assistant-bubble-content {
            display: grid;
            gap: 10px;
        }

        .assistant-bubble-content > * {
            margin: 0;
        }

        .assistant-bubble-content p {
            color: inherit;
            line-height: 1.62;
        }

        .assistant-bubble-content strong {
            font-weight: 700;
        }

        .assistant-bubble-content ul,
        .assistant-bubble-content ol {
            margin: 0;
            padding-left: 18px;
            display: grid;
            gap: 7px;
        }

        .assistant-bubble-content li {
            line-height: 1.55;
        }

        .assistant-bubble.atlas .assistant-bubble-content ul li::marker,
        .assistant-bubble.atlas .assistant-bubble-content ol li::marker {
            color: rgba(169, 63, 97, 0.8);
        }

        .assistant-section-title {
            font-size: 0.76rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(123, 107, 95, 0.72);
            margin-bottom: -2px;
        }

        .assistant-bubble.user .assistant-section-title {
            color: rgba(255, 243, 232, 0.66);
        }

        .assistant-form {
            display: grid;
            gap: 10px;
        }

        .assistant-plan {
            display: grid;
            gap: 10px;
            padding: 14px;
            border-radius: 24px;
            border: 1px solid rgba(227, 216, 203, 0.95);
            background: rgba(255,255,255,0.62);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.82);
        }

        .assistant-plan[hidden] {
            display: none;
        }

        .assistant-plan-title {
            font-size: 0.74rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(122, 105, 92, 0.74);
        }

        .assistant-plan-name {
            font-family: "Inter Tight", "Inter", "Avenir Next", sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: #1b1512;
            letter-spacing: -0.03em;
        }

        .assistant-plan-list {
            display: grid;
            gap: 8px;
            margin: 0;
            padding: 0 0 0 18px;
        }

        .assistant-plan-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .assistant-plan-action {
            border: 1px solid rgba(220, 207, 191, 0.92);
            background: rgba(255, 253, 249, 0.9);
            color: rgba(94, 77, 67, 0.92);
            border-radius: 999px;
            padding: 8px 12px;
            font: inherit;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
        }

        .assistant-composer {
            min-height: 0;
            flex-shrink: 0;
        }

        .assistant-textarea {
            width: 100%;
            min-height: 84px;
            max-height: 180px;
            resize: none;
            border-radius: 22px;
            border: 1px solid rgba(220, 207, 191, 0.9);
            background: rgba(255, 255, 255, 0.88);
            color: #1b1714;
            padding: 15px 16px;
            font: inherit;
            line-height: 1.5;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.76);
        }

        .assistant-textarea::placeholder {
            color: rgba(121, 104, 92, 0.58);
        }

        .assistant-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .assistant-status {
            font-size: 0.8rem;
            color: rgba(114, 96, 84, 0.72);
            line-height: 1.4;
        }

        .assistant-send {
            border: 0;
            border-radius: 999px;
            padding: 12px 18px;
            min-width: 110px;
            background: linear-gradient(180deg, #181310, #2b221d);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 12px 28px rgba(35, 28, 23, 0.16);
        }

        .assistant-send:disabled {
            opacity: 0.62;
            cursor: wait;
        }

        .assistant-chip {
            display: inline-flex;
            align-items: center;
            margin-top: 8px;
            margin-right: 6px;
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(244, 237, 228, 0.96);
            color: rgba(88, 73, 64, 0.88);
            font-size: 0.78rem;
            border: 1px solid rgba(227, 214, 201, 0.88);
            cursor: pointer;
        }

        .assistant-prompt-list::-webkit-scrollbar,
        .assistant-feed::-webkit-scrollbar {
            width: 8px;
        }

        .assistant-prompt-list::-webkit-scrollbar-thumb,
        .assistant-feed::-webkit-scrollbar-thumb {
            background: rgba(199, 183, 167, 0.9);
            border-radius: 999px;
            border: 2px solid rgba(255, 250, 244, 0.9);
        }

        .assistant-prompt-list::-webkit-scrollbar-track,
        .assistant-feed::-webkit-scrollbar-track {
            background: transparent;
        }

        .assistant p {
            color: rgba(94, 79, 69, 0.84);
            line-height: 1.5;
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
                min-height: auto;
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
            inset: 92px 510px 56px 64px;
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
                0 28px 90px rgba(55, 41, 24, 0.16),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            pointer-events: auto;
            min-width: 360px;
            min-height: 280px;
            transition: box-shadow 0.22s ease, transform 0.22s ease;
        }

        .os-app-window.is-entering {
            animation: os-window-enter 0.26s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .os-app-window.active {
            box-shadow:
                0 36px 110px rgba(55, 41, 24, 0.22),
                inset 0 1px 0 rgba(255, 255, 255, 0.92);
            transform: translateY(-1px);
        }

        @keyframes os-window-enter {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.985);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .os-app-window-bar,
        .os-inline-window-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 18px;
            background: rgba(255, 250, 245, 0.9);
            border-bottom: 1px solid rgba(214, 201, 184, 0.68);
            cursor: grab;
        }

        .os-app-window-bar:active {
            cursor: grabbing;
        }

        .os-app-window-dots,
        .os-inline-window-dots {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .os-app-window-dot,
        .os-inline-window-dots span {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 0;
            cursor: pointer;
            font: inherit;
        }

        .os-app-window-dot:hover {
            filter: brightness(0.96);
            transform: scale(1.04);
        }

        .os-app-window-dots .close,
        .os-inline-window-dots span:nth-child(1) { background: #ff7965; }
        .os-app-window-dots .minimize,
        .os-inline-window-dots span:nth-child(2) { background: #f6c85e; }
        .os-app-window-dots .maximize,
        .os-inline-window-dots span:nth-child(3) { background: #68c06a; }

        .os-app-window-title,
        .os-inline-window-title {
            flex: 1;
            min-width: 0;
            text-align: center;
            font-family: "Inter Tight", "Inter", "Avenir Next", sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 0;
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

        .os-app-window.is-minimized {
            display: none;
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
            position: relative;
            overflow: hidden;
        }

        body.os-embed-mode .founder-main-inner::before,
        body.os-embed-mode .workspace-main-inner::before,
        body.os-embed-mode .tracker-main-inner::before,
        body.os-embed-mode .marketing-main-inner::before,
        body.os-embed-mode .settings-main-inner::before,
        body.os-embed-mode .learning-main-inner::before,
        body.os-embed-mode .notifications-main-inner::before,
        body.os-embed-mode .tools-main-inner::before,
        body.os-embed-mode .media-main-inner::before,
        body.os-embed-mode .activity-main-inner::before,
        body.os-embed-mode .wallet-main-inner::before,
        body.os-embed-mode .commerce-main-inner::before,
        body.os-embed-mode .ops-main-inner::before,
        body.os-embed-mode .tasks-main-inner::before,
        body.os-embed-mode .analytics-main-inner::before,
        body.os-embed-mode .atlas-frame-main-inner::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 92% 6%, rgba(234, 197, 201, 0.14), transparent 0 20%),
                radial-gradient(circle at 8% 100%, rgba(233, 224, 214, 0.18), transparent 0 18%);
            pointer-events: none;
        }

        body.os-embed-mode .founder-main-inner > *,
        body.os-embed-mode .workspace-main-inner > *,
        body.os-embed-mode .tracker-main-inner > *,
        body.os-embed-mode .marketing-main-inner > *,
        body.os-embed-mode .settings-main-inner > *,
        body.os-embed-mode .learning-main-inner > *,
        body.os-embed-mode .notifications-main-inner > *,
        body.os-embed-mode .tools-main-inner > *,
        body.os-embed-mode .media-main-inner > *,
        body.os-embed-mode .activity-main-inner > *,
        body.os-embed-mode .wallet-main-inner > *,
        body.os-embed-mode .commerce-main-inner > *,
        body.os-embed-mode .ops-main-inner > *,
        body.os-embed-mode .tasks-main-inner > *,
        body.os-embed-mode .analytics-main-inner > *,
        body.os-embed-mode .atlas-frame-main-inner > * {
            position: relative;
            z-index: 1;
        }

        body.os-embed-mode .workspace-main-inner h1,
        body.os-embed-mode .learning-main-inner h1,
        body.os-embed-mode .tools-main-inner h1,
        body.os-embed-mode .commerce-main-inner h1,
        body.os-embed-mode .tracker-main-inner h1,
        body.os-embed-mode .marketing-main-inner h1,
        body.os-embed-mode .settings-main-inner h1,
        body.os-embed-mode .tasks-main-inner h1,
        body.os-embed-mode .analytics-main-inner h1,
        body.os-embed-mode .wallet-main-inner h1,
        body.os-embed-mode .activity-main-inner h1,
        body.os-embed-mode .media-main-inner h1 {
            font-family: "Inter Tight", "Inter", "Avenir Next", sans-serif !important;
            font-weight: 700 !important;
            letter-spacing: -0.03em !important;
            line-height: 1.02 !important;
            margin-bottom: 8px !important;
            color: #191513 !important;
        }

        body.os-embed-mode .workspace-main-inner h2,
        body.os-embed-mode .learning-main-inner h2,
        body.os-embed-mode .tools-main-inner h2,
        body.os-embed-mode .commerce-main-inner h2,
        body.os-embed-mode .tracker-main-inner h2,
        body.os-embed-mode .marketing-main-inner h2,
        body.os-embed-mode .settings-main-inner h2,
        body.os-embed-mode .tasks-main-inner h2,
        body.os-embed-mode .analytics-main-inner h2,
        body.os-embed-mode .wallet-main-inner h2,
        body.os-embed-mode .activity-main-inner h2,
        body.os-embed-mode .media-main-inner h2 {
            font-family: "Inter Tight", "Inter", "Avenir Next", sans-serif !important;
            font-weight: 700 !important;
            letter-spacing: -0.02em !important;
            color: #201916 !important;
        }

        body.os-embed-mode .workspace-main-inner p,
        body.os-embed-mode .learning-main-inner p,
        body.os-embed-mode .tools-main-inner p,
        body.os-embed-mode .commerce-main-inner p,
        body.os-embed-mode .tracker-main-inner p,
        body.os-embed-mode .marketing-main-inner p,
        body.os-embed-mode .settings-main-inner p,
        body.os-embed-mode .tasks-main-inner p,
        body.os-embed-mode .analytics-main-inner p,
        body.os-embed-mode .wallet-main-inner p,
        body.os-embed-mode .activity-main-inner p,
        body.os-embed-mode .media-main-inner p {
            color: rgba(98, 84, 74, 0.9) !important;
        }

        body.os-embed-mode .workspace-stage-tab,
        body.os-embed-mode .commerce-view-tab,
        body.os-embed-mode .btn,
        body.os-embed-mode .learning-status,
        body.os-embed-mode .commerce-cta,
        body.os-embed-mode .commerce-secondary,
        body.os-embed-mode .tool-card-cta,
        body.os-embed-mode .tool-card-secondary {
            border-radius: 999px !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.55);
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        body.os-embed-mode .workspace-stage-tab:hover,
        body.os-embed-mode .commerce-view-tab:hover,
        body.os-embed-mode .btn:hover,
        body.os-embed-mode .learning-status:hover,
        body.os-embed-mode .commerce-cta:hover,
        body.os-embed-mode .commerce-secondary:hover,
        body.os-embed-mode .tool-card-cta:hover,
        body.os-embed-mode .tool-card-secondary:hover {
            transform: translateY(-1px);
        }

        body.os-embed-mode .workspace-stage-tab.active,
        body.os-embed-mode .commerce-view-tab.active {
            background: #ece6db !important;
            border-color: rgba(215, 201, 184, 0.95) !important;
            color: #211a16 !important;
        }

        body.os-embed-mode .card,
        body.os-embed-mode .stack-item,
        body.os-embed-mode .workspace-rail-item,
        body.os-embed-mode .learning-card,
        body.os-embed-mode .tool-card,
        body.os-embed-mode .tools-highlight,
        body.os-embed-mode .commerce-card,
        body.os-embed-mode .commerce-metric,
        body.os-embed-mode .rail-item,
        body.os-embed-mode .mini-note,
        body.os-embed-mode .drawer-comment,
        body.os-embed-mode .learning-banner,
        body.os-embed-mode .commerce-banner,
        body.os-embed-mode .workspace-stage-helper,
        body.os-embed-mode .commerce-helper {
            background: rgba(255, 253, 249, 0.94) !important;
            border: 1px solid rgba(220, 207, 191, 0.72) !important;
            box-shadow:
                0 12px 32px rgba(52, 41, 26, 0.045),
                inset 0 1px 0 rgba(255, 255, 255, 0.5) !important;
        }

        body.os-embed-mode .learning-card,
        body.os-embed-mode .tool-card,
        body.os-embed-mode .commerce-card {
            border-radius: 20px !important;
        }

        body.os-embed-mode .workspace-stage-helper,
        body.os-embed-mode .commerce-helper,
        body.os-embed-mode .learning-banner,
        body.os-embed-mode .commerce-banner {
            border-radius: 18px !important;
        }

        body.os-embed-mode .workspace-main-inner input,
        body.os-embed-mode .workspace-main-inner textarea,
        body.os-embed-mode .workspace-main-inner select,
        body.os-embed-mode .commerce-main-inner input,
        body.os-embed-mode .commerce-main-inner textarea,
        body.os-embed-mode .commerce-main-inner select {
            border-radius: 14px !important;
            border: 1px solid rgba(220, 207, 191, 0.88) !important;
            background: rgba(255, 255, 255, 0.9) !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
        }

        body.os-embed-mode .workspace-main-inner .pill,
        body.os-embed-mode .workspace-main-inner .learning-badge,
        body.os-embed-mode .workspace-main-inner .drawer-badge,
        body.os-embed-mode .tools-main-inner .highlight-badge,
        body.os-embed-mode .commerce-main-inner .commerce-chip {
            border-radius: 999px !important;
            background: #f1ebe3 !important;
            color: #74695f !important;
            border: 1px solid rgba(220, 207, 191, 0.7);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.55);
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
        $showDefaultAssistant = false;
        $assistantContext = [];
        $assistantPrompts = [];
        $assistantTimeline = [];
        $assistantSummary = [
            'thread_key' => '',
            'label' => 'New founder chat',
            'pinned_plan' => [],
        ];
        $assistantThreads = [];
        if ($authUser?->role === 'admin') {
            $dashboardLabel = 'Admin';
        } elseif ($authUser?->role === 'mentor') {
            $dashboardLabel = 'Mentor';
        } elseif ($authUser?->role === 'founder' && trim($__env->yieldContent('page_class')) === 'founder-home-page' && !$osEmbedMode) {
            $authUser->loadMissing([
                'company.intelligence',
                'weeklyState',
                'commercialSummary',
            ]);

            $company = $authUser->company;
            $weeklyState = $authUser->weeklyState;
            $commercialSummary = $authUser->commercialSummary;
            $assistantThread = $authUser->conversationThreads()
                ->where('thread_key', 'like', 'atlas-assistant%')
                ->orderByDesc('last_activity_at')
                ->orderByDesc('updated_at')
                ->first();
            $assistantThreadMeta = is_array($assistantThread?->meta_json) ? $assistantThread->meta_json : [];
            $assistantTimeline = collect(is_array($assistantThreadMeta['messages'] ?? null) ? $assistantThreadMeta['messages'] : [])
                ->filter(fn ($message) => is_array($message) && !empty($message['text']))
                ->take(-12)
                ->values()
                ->all();
            $assistantSummary = [
                'thread_key' => (string) ($assistantThread?->thread_key ?? ''),
                'label' => (string) ($assistantThreadMeta['label'] ?? 'New founder chat'),
                'pinned_plan' => is_array($assistantThreadMeta['pinned_plan'] ?? null) ? $assistantThreadMeta['pinned_plan'] : [],
            ];
            $assistantThreads = $authUser->conversationThreads()
                ->where('thread_key', 'like', 'atlas-assistant%')
                ->orderByDesc('last_activity_at')
                ->orderByDesc('updated_at')
                ->limit(8)
                ->get()
                ->map(function ($thread): array {
                    $meta = is_array($thread->meta_json) ? $thread->meta_json : [];

                    return [
                        'thread_key' => (string) $thread->thread_key,
                        'label' => (string) ($meta['label'] ?? 'Founder chat'),
                        'latest_message' => (string) ($thread->latest_message ?? ''),
                    ];
                })
                ->values()
                ->all();
            $showDefaultAssistant = true;
            $assistantContext = [
                'founder_name' => (string) ($authUser->full_name ?: $authUser->username ?: 'Founder'),
                'company_name' => (string) ($company?->company_name ?: 'your company'),
                'focus' => (string) ($weeklyState?->weekly_focus ?: 'Clarify the next revenue move'),
                'open_tasks' => (int) ($weeklyState?->open_tasks ?? 0),
                'progress' => (int) ($weeklyState?->weekly_progress_percent ?? 0),
                'orders' => (int) ($commercialSummary?->order_count ?? 0),
                'bookings' => (int) ($commercialSummary?->booking_count ?? 0),
                'revenue' => strtoupper((string) ($commercialSummary?->currency ?? 'USD')) . ' ' . number_format((float) ($commercialSummary?->gross_revenue ?? 0), 0),
            ];
            $assistantPrompts = [
                'What should I focus on today to move revenue fastest?',
                'Review my offer and tell me what is weak.',
                'What is blocking conversions right now in my OS?',
                'Give me the next three actions using Sell Like Crazy thinking.',
                'How do I get my first 3 paying customers this week?',
            ];
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
    @if ($showDefaultAssistant)
        <aside class="assistant" data-os-assistant>
            <div class="assistant-header">
                <div>
                    <div class="assistant-kicker">
                        <span class="assistant-kicker-mark"></span>
                        <span>Atlas Assistant</span>
                    </div>
                    <div class="assistant-title">Chat with Atlas</div>
                    <p class="assistant-subtitle">
                        Ask anything about your company, orders, bookings, tasks, learnings, website, or what to do next inside Hatchers OS.
                    </p>
                </div>
                <button class="assistant-toggle" type="button" data-assistant-toggle aria-label="Collapse assistant">−</button>
            </div>
            <div class="assistant-body">
                <div class="assistant-session">
                    <div class="assistant-session-label">
                        <div class="assistant-session-kicker">Current chat</div>
                        <div class="assistant-session-title" data-assistant-thread-label>{{ $assistantSummary['label'] }}</div>
                    </div>
                    <button class="assistant-session-reset" type="button" data-assistant-reset>New chat</button>
                </div>

                @if (!empty($assistantThreads))
                    <div class="assistant-thread-list" data-assistant-thread-list>
                        @foreach ($assistantThreads as $assistantThreadItem)
                            <button
                                class="assistant-thread-item {{ ($assistantSummary['thread_key'] ?? '') === ($assistantThreadItem['thread_key'] ?? '') ? 'active' : '' }}"
                                type="button"
                                data-assistant-thread-item
                                data-thread-key="{{ (string) ($assistantThreadItem['thread_key'] ?? '') }}"
                            >
                                <span class="assistant-thread-name">{{ (string) ($assistantThreadItem['label'] ?? 'Founder chat') }}</span>
                                <span class="assistant-thread-preview">{{ (string) ($assistantThreadItem['latest_message'] ?? 'Open this chat') }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="assistant-snapshot">
                    <div class="assistant-snapshot-top">
                        <div>
                            <div class="assistant-snapshot-title">Live founder context</div>
                            <div class="assistant-snapshot-focus">{{ $assistantContext['focus'] }}</div>
                        </div>
                    </div>
                    <div class="assistant-snapshot-grid">
                        <div class="assistant-stat">
                            <span class="assistant-stat-label">Tasks</span>
                            <span class="assistant-stat-value">{{ $assistantContext['open_tasks'] }}</span>
                        </div>
                        <div class="assistant-stat">
                            <span class="assistant-stat-label">Progress</span>
                            <span class="assistant-stat-value">{{ $assistantContext['progress'] }}%</span>
                        </div>
                        <div class="assistant-stat">
                            <span class="assistant-stat-label">Flow</span>
                            <span class="assistant-stat-value">{{ $assistantContext['orders'] + $assistantContext['bookings'] }}</span>
                        </div>
                        <div class="assistant-stat">
                            <span class="assistant-stat-label">Revenue</span>
                            <span class="assistant-stat-value">{{ $assistantContext['revenue'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="assistant-plan" data-assistant-plan @if (empty($assistantSummary['pinned_plan']['steps'] ?? [])) hidden @endif>
                    <div class="assistant-plan-title">Pinned from Atlas</div>
                    <div class="assistant-plan-name" data-assistant-plan-name>{{ (string) ($assistantSummary['pinned_plan']['title'] ?? "Today's plan") }}</div>
                    <ol class="assistant-plan-list" data-assistant-plan-list>
                        @foreach ((array) ($assistantSummary['pinned_plan']['steps'] ?? []) as $planStep)
                            <li>{{ (string) $planStep }}</li>
                        @endforeach
                    </ol>
                    <div class="assistant-plan-actions">
                        <button class="assistant-plan-action" type="button" data-assistant-save-plan>Turn into tasks</button>
                        <button class="assistant-plan-action" type="button" data-assistant-open-tasks>Open Tasks</button>
                    </div>
                </div>

                <div class="assistant-prompts">
                    <div class="assistant-prompts-head">
                        <div class="assistant-prompts-title">Try one of these</div>
                        <button class="assistant-prompts-toggle" type="button" data-assistant-prompts-toggle>Hide starters</button>
                    </div>
                    <div class="assistant-prompt-list">
                        @foreach ($assistantPrompts as $prompt)
                            <button class="assistant-prompt" type="button" data-assistant-prompt="{{ $prompt }}">{{ $prompt }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="assistant-feed" data-assistant-feed>
                    @if (!empty($assistantTimeline))
                        @foreach ($assistantTimeline as $timelineMessage)
                            @php
                                $timelineType = (string) ($timelineMessage['type'] ?? 'atlas');
                                $timelineTitle = (string) ($timelineMessage['title'] ?? ($timelineType === 'user' ? 'You' : 'Atlas Assistant'));
                                $timelineActions = collect(is_array($timelineMessage['actions'] ?? null) ? $timelineMessage['actions'] : [])
                                    ->filter(fn ($action) => is_array($action) && !empty($action['label']))
                                    ->values()
                                    ->all();
                            @endphp
                            <div class="assistant-bubble {{ $timelineType === 'user' ? 'user' : 'atlas' }}">
                                <span class="assistant-bubble-title">{{ $timelineTitle }}</span>
                                <div class="assistant-bubble-content" data-assistant-message-content data-message-type="{{ $timelineType === 'user' ? 'user' : 'atlas' }}">{{ (string) ($timelineMessage['text'] ?? '') }}</div>
                                @foreach ($timelineActions as $timelineAction)
                                    <button
                                        class="assistant-chip"
                                        type="button"
                                        data-assistant-action="1"
                                        data-assistant-workspace="{{ (string) ($timelineAction['workspace_key'] ?? '') }}"
                                        data-assistant-href="{{ (string) ($timelineAction['href'] ?? '') }}"
                                    >{{ (string) ($timelineAction['label'] ?? '') }}</button>
                                @endforeach
                            </div>
                        @endforeach
                    @else
                        <div class="assistant-bubble atlas">
                            <span class="assistant-bubble-title">Atlas Assistant</span>
                            <div class="assistant-bubble-content" data-assistant-message-content data-message-type="atlas">I’m looking at {{ $assistantContext['company_name'] }} with your live Hatchers OS context. Ask me what you are stuck on, what you should focus on, or what you do not understand and I’ll coach you through it.</div>
                        </div>
                    @endif
                </div>

                <div class="assistant-composer">
                    <form class="assistant-form" data-assistant-form>
                        <textarea class="assistant-textarea" data-assistant-input placeholder="Message Atlas about any founder question, blocker, offer, campaign, order, booking, task, or next move."></textarea>
                        <div class="assistant-row">
                            <div class="assistant-status" data-assistant-status>Atlas sees your Hatchers OS context and will respond like your founder mentor.</div>
                            <button class="assistant-send" data-assistant-send type="submit">Ask Atlas</button>
                        </div>
                    </form>
                </div>
            </div>
        </aside>
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
            const resetButton = assistant.querySelector('[data-assistant-reset]');
            const threadLabel = assistant.querySelector('[data-assistant-thread-label]');
            const threadList = assistant.querySelector('[data-assistant-thread-list]');
            const planPanel = assistant.querySelector('[data-assistant-plan]');
            const planName = assistant.querySelector('[data-assistant-plan-name]');
            const planList = assistant.querySelector('[data-assistant-plan-list]');
            const savePlanButton = assistant.querySelector('[data-assistant-save-plan]');
            const openTasksButton = assistant.querySelector('[data-assistant-open-tasks]');
            const promptButtons = assistant.querySelectorAll('[data-assistant-prompt]');
            const promptsToggle = assistant.querySelector('[data-assistant-prompts-toggle]');
            const existingActionButtons = assistant.querySelectorAll('[data-assistant-action]');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            let currentThreadKey = @json($assistantSummary['thread_key'] ?? '');

            const setOpen = (open) => {
                assistant.classList.toggle('collapsed', !open);
                if (toggle) toggle.textContent = open ? '−' : '+';
            };

            const hasConversation = () => assistant.querySelectorAll('.assistant-bubble').length > 1;

            const syncConversationMode = ({ forceOpenPrompts = false } = {}) => {
                const activeConversation = hasConversation();
                assistant.classList.toggle('has-conversation', activeConversation);

                if (!activeConversation) {
                    assistant.classList.remove('prompts-open');
                } else if (forceOpenPrompts) {
                    assistant.classList.add('prompts-open');
                } else if (!assistant.classList.contains('prompts-open')) {
                    assistant.classList.remove('prompts-open');
                }

                if (promptsToggle) {
                    const promptsVisible = !activeConversation || assistant.classList.contains('prompts-open');
                    promptsToggle.textContent = promptsVisible ? 'Hide starters' : 'Show starters';
                }
            };

            const autosizeTextarea = () => {
                if (!textarea) return;
                textarea.style.height = 'auto';
                textarea.style.height = `${Math.min(textarea.scrollHeight, 180)}px`;
            };

            const setThreadLabel = (text) => {
                if (!threadLabel) return;
                threadLabel.textContent = text || 'Founder chat';
            };

            const setActiveThreadButton = (threadKey) => {
                assistant.querySelectorAll('[data-assistant-thread-item]').forEach((button) => {
                    button.classList.toggle('active', button.dataset.threadKey === threadKey);
                });
            };

            const prependThreadButton = (threadKey, label, preview = 'New chat started') => {
                if (!threadList || !threadKey) return;

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'assistant-thread-item active';
                button.dataset.assistantThreadItem = '1';
                button.dataset.threadKey = threadKey;
                button.innerHTML = `<span class="assistant-thread-name"></span><span class="assistant-thread-preview"></span>`;
                button.querySelector('.assistant-thread-name').textContent = label || 'Founder chat';
                button.querySelector('.assistant-thread-preview').textContent = preview;
                button.addEventListener('click', () => loadThread(threadKey));

                threadList.querySelectorAll('[data-assistant-thread-item]').forEach((item) => item.classList.remove('active'));
                threadList.prepend(button);
            };

            const desktopOpenApp = (workspaceKey, fallbackHref = '') => {
                if (workspaceKey) {
                    window.dispatchEvent(new CustomEvent('hatchers:open-app', {
                        detail: { key: workspaceKey, href: fallbackHref || '' },
                    }));
                    return;
                }

                if (fallbackHref) {
                    window.location.href = fallbackHref;
                }
            };

            const buildPinnedPlanFromText = (text, actions = []) => {
                const steps = [];
                const normalized = String(text || '').replace(/\r\n/g, '\n');

                normalized.split('\n').forEach((line) => {
                    const trimmed = line.trim();
                    if (!trimmed) return;
                    const match = trimmed.match(/^(?:\d+\.|[-*])\s+(.+)$/);
                    if (match) {
                        steps.push(match[1].trim());
                    }
                });

                if (steps.length < 2 && Array.isArray(actions)) {
                    actions.forEach((action) => {
                        const label = String(action?.cta || action?.title || action?.label || '').trim();
                        if (label) {
                            steps.push(label);
                        }
                    });
                }

                return steps.slice(0, 3);
            };

            const setPinnedPlan = (steps, title = "Today's plan") => {
                if (!planPanel || !planList || !planName) return;

                const cleanSteps = (Array.isArray(steps) ? steps : [])
                    .map((step) => String(step || '').trim())
                    .filter(Boolean)
                    .slice(0, 3);

                if (!cleanSteps.length) {
                    planPanel.hidden = true;
                    return;
                }

                planPanel.hidden = false;
                planName.textContent = title;
                planList.innerHTML = cleanSteps.map((step) => `<li>${step}</li>`).join('');
            };

            const currentPinnedPlanSteps = () => {
                if (!planList || !planPanel || planPanel.hidden) return [];
                return Array.from(planList.querySelectorAll('li')).map((item) => item.textContent?.trim() || '').filter(Boolean);
            };

            const appendInlineFormattedText = (target, text) => {
                const parts = String(text || '').split(/(\*\*[^*]+\*\*)/g).filter(Boolean);
                parts.forEach((part) => {
                    if (part.startsWith('**') && part.endsWith('**')) {
                        const strong = document.createElement('strong');
                        strong.textContent = part.slice(2, -2);
                        target.appendChild(strong);
                    } else {
                        target.appendChild(document.createTextNode(part));
                    }
                });
            };

            const buildTextBlock = (line, tagName = 'p') => {
                const element = document.createElement(tagName);
                appendInlineFormattedText(element, line);
                return element;
            };

            const renderAssistantContent = (container, text, type = 'atlas') => {
                if (!container) return;

                const normalized = String(text || '').replace(/\r\n/g, '\n').trim();
                container.innerHTML = '';

                if (!normalized) {
                    return;
                }

                const blocks = normalized.split(/\n\s*\n/);

                blocks.forEach((block) => {
                    const lines = block.split('\n').map((line) => line.trim()).filter(Boolean);
                    if (!lines.length) return;

                    if (lines.every((line) => /^[-*]\s+/.test(line))) {
                        const list = document.createElement('ul');
                        lines.forEach((line) => {
                            const item = document.createElement('li');
                            appendInlineFormattedText(item, line.replace(/^[-*]\s+/, ''));
                            list.appendChild(item);
                        });
                        container.appendChild(list);
                        return;
                    }

                    if (lines.every((line) => /^\d+\.\s+/.test(line))) {
                        const list = document.createElement('ol');
                        lines.forEach((line) => {
                            const item = document.createElement('li');
                            appendInlineFormattedText(item, line.replace(/^\d+\.\s+/, ''));
                            list.appendChild(item);
                        });
                        container.appendChild(list);
                        return;
                    }

                    if (type !== 'user' && lines.length > 1 && /^[A-Za-z].{0,48}:$/.test(lines[0])) {
                        const sectionTitle = document.createElement('div');
                        sectionTitle.className = 'assistant-section-title';
                        sectionTitle.textContent = lines[0].replace(/:$/, '');
                        container.appendChild(sectionTitle);

                        const bodyLines = lines.slice(1);
                        const bodyParagraph = buildTextBlock(bodyLines.join(' '), 'p');
                        container.appendChild(bodyParagraph);
                        return;
                    }

                    lines.forEach((line) => {
                        container.appendChild(buildTextBlock(line, 'p'));
                    });
                });
            };

            const addBubble = (type, text, actions = [], options = {}) => {
                const bubble = document.createElement('div');
                bubble.className = `assistant-bubble ${type}`;

                const title = document.createElement('span');
                title.className = 'assistant-bubble-title';
                title.textContent = options.title || (type === 'user' ? 'You' : 'Atlas Assistant');
                bubble.appendChild(title);

                const content = document.createElement('div');
                content.className = 'assistant-bubble-content';
                content.dataset.assistantMessageContent = '1';
                content.dataset.messageType = type;
                renderAssistantContent(content, text, type);
                bubble.appendChild(content);

                if (Array.isArray(actions) && actions.length) {
                    actions.slice(0, 3).forEach((action) => {
                        const label = action.cta || action.title || action.platform || '';
                        if (!label) return;
                        const chip = document.createElement('button');
                        chip.type = 'button';
                        chip.className = 'assistant-chip';
                        chip.textContent = label;
                        chip.dataset.assistantAction = '1';
                        chip.dataset.assistantWorkspace = action.os_workspace_key || '';
                        chip.dataset.assistantHref = action.os_href || '';
                        chip.addEventListener('click', () => {
                            desktopOpenApp(chip.dataset.assistantWorkspace, chip.dataset.assistantHref);
                        });
                        bubble.appendChild(chip);
                    });
                }

                feed?.appendChild(bubble);
                if (feed) feed.scrollTop = feed.scrollHeight;
                if (type === 'user') {
                    const trimmed = String(text || '').trim();
                    setThreadLabel(trimmed.slice(0, 42) + (trimmed.length > 42 ? '…' : ''));
                }
                if (type === 'atlas') {
                    const planSteps = buildPinnedPlanFromText(text, actions);
                    setPinnedPlan(planSteps);
                }
                syncConversationMode();
            };

            toggle?.addEventListener('click', () => {
                setOpen(assistant.classList.contains('collapsed'));
            });

            promptButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    if (!textarea) return;
                    textarea.value = button.dataset.assistantPrompt || '';
                    autosizeTextarea();
                    textarea.focus();
                });
            });

            promptsToggle?.addEventListener('click', () => {
                const activeConversation = hasConversation();
                if (!activeConversation) {
                    return;
                }

                assistant.classList.toggle('prompts-open');
                syncConversationMode({ forceOpenPrompts: assistant.classList.contains('prompts-open') });
            });

            existingActionButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    desktopOpenApp(button.dataset.assistantWorkspace || '', button.dataset.assistantHref || '');
                });
            });

            const bindThreadButton = (button) => {
                button.addEventListener('click', () => {
                    loadThread(button.dataset.threadKey || '');
                });
            };

            assistant.querySelectorAll('[data-assistant-thread-item]').forEach(bindThreadButton);

            const loadThread = async (threadKey) => {
                if (!threadKey) return;
                if (status) status.textContent = 'Loading chat...';

                try {
                    const response = await fetch('/assistant/chat/thread', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ thread_key: threadKey }),
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        if (status) status.textContent = 'Could not load that chat.';
                        return;
                    }

                    currentThreadKey = data.thread_key || threadKey;
                    if (feed) {
                        feed.innerHTML = '';
                    }

                    (Array.isArray(data.messages) ? data.messages : []).forEach((message) => {
                        addBubble(
                            message.type === 'user' ? 'user' : 'atlas',
                            message.text || '',
                            message.actions || [],
                            { title: message.title || (message.type === 'user' ? 'You' : 'Atlas Assistant') }
                        );
                    });

                    setThreadLabel(data.label || 'Founder chat');
                    setActiveThreadButton(currentThreadKey);
                    setPinnedPlan(data.pinned_plan?.steps || [], data.pinned_plan?.title || "Today's plan");
                    syncConversationMode();
                    if (status) status.textContent = 'Chat loaded.';
                } catch (error) {
                    if (status) status.textContent = 'Could not load that chat.';
                }
            };

            resetButton?.addEventListener('click', async () => {
                if (sendButton) sendButton.disabled = true;
                resetButton.disabled = true;
                if (status) status.textContent = 'Starting a fresh chat...';

                try {
                    const response = await fetch('/assistant/chat/reset', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({}),
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        if (status) status.textContent = 'Could not reset chat right now.';
                        return;
                    }

                    if (feed) {
                        feed.innerHTML = '';
                    }

                    currentThreadKey = data.thread_key || '';
                    setThreadLabel(data.label || 'New founder chat');
                    setActiveThreadButton(currentThreadKey);
                    prependThreadButton(currentThreadKey, data.label || 'New founder chat');
                    setPinnedPlan([]);
                    addBubble('atlas', data.reply || 'New chat started. Ask Atlas anything.');
                    assistant.classList.remove('has-conversation', 'prompts-open');
                    syncConversationMode({ forceOpenPrompts: true });
                    if (status) status.textContent = 'Fresh chat ready.';
                } catch (error) {
                    if (status) status.textContent = 'Could not reset chat right now.';
                } finally {
                    if (sendButton) sendButton.disabled = false;
                    resetButton.disabled = false;
                }
            });

            savePlanButton?.addEventListener('click', async () => {
                const steps = currentPinnedPlanSteps();
                if (!steps.length) {
                    if (status) status.textContent = 'There is no pinned plan to save yet.';
                    return;
                }

                savePlanButton.disabled = true;
                if (status) status.textContent = 'Saving this plan into your OS tasks...';

                try {
                    const response = await fetch('/assistant/chat/tasks', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            thread_key: currentThreadKey,
                            title: planName?.textContent || "Today's plan",
                            steps,
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        if (status) status.textContent = data.error || 'Could not save tasks.';
                        return;
                    }

                    addBubble('atlas', data.reply || 'Saved into your tasks.', data.actions || []);
                    if (status) status.textContent = `Saved ${data.created_count || steps.length} tasks into Hatchers OS.`;
                } catch (error) {
                    if (status) status.textContent = 'Could not save tasks.';
                } finally {
                    savePlanButton.disabled = false;
                }
            });

            openTasksButton?.addEventListener('click', () => {
                desktopOpenApp('tasks', '/tasks');
            });

            assistant.querySelectorAll('[data-assistant-message-content]').forEach((node) => {
                const type = node.dataset.messageType || 'atlas';
                renderAssistantContent(node, node.textContent || '', type);
            });
            if (feed) {
                feed.scrollTop = feed.scrollHeight;
            }

            form?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const message = textarea?.value.trim();
                if (!message) return;

                addBubble('user', message);
                textarea.value = '';
                autosizeTextarea();
                if (status) status.textContent = 'Atlas is reviewing your founder context...';
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
                            thread_key: currentThreadKey,
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        addBubble('atlas', data.error || 'Hatchers AI could not respond right now.');
                        if (status) status.textContent = 'Atlas is temporarily unavailable.';
                    } else {
                        currentThreadKey = data.thread_key || currentThreadKey;
                        setActiveThreadButton(currentThreadKey);
                        addBubble('atlas', data.reply || 'Hatchers AI is here.', data.actions || []);
                        if (data.refresh) {
                            if (status) status.textContent = 'Atlas updated your workspace. Refreshing summary...';
                            window.setTimeout(() => window.location.reload(), 900);
                        } else if (status) {
                            status.textContent = 'Atlas is synced with your OS context.';
                        }
                    }
                } catch (error) {
                    addBubble('atlas', 'Hatchers AI could not respond right now.');
                    if (status) status.textContent = 'Connection issue. Please try again.';
                } finally {
                    if (sendButton) sendButton.disabled = false;
                }
            });

            textarea?.addEventListener('input', autosizeTextarea);
            autosizeTextarea();
            syncConversationMode();
            setOpen(true);
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
            if (!page || document.body.classList.contains('os-embed-mode')) return;

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
            const dock = document.querySelector('[data-os-desktop-dock]');
            if (!desktop || !host) return;

            const launcherNodes = Array.from(document.querySelectorAll('[data-launcher-route]'));
            const routeMap = {};
            const geometryStorageKey = 'hatchers-os-window-geometry';
            launcherNodes.forEach((node) => {
                const key = node.dataset.launcherKey;
                if (!key || routeMap[key]) return;
                routeMap[key] = {
                    key,
                    label: node.dataset.launcherLabel || key,
                    route: node.dataset.launcherRoute || '/',
                    icon: node.dataset.launcherIcon || 'file',
                    className: node.dataset.launcherClass || '',
                };
            });

            let zIndex = 20;
            const windows = new Map();
            const appPresets = {
                'ai-tools': { width: 980, height: 680 },
                'website': { width: 1040, height: 700 },
                'commerce': { width: 940, height: 660 },
                'marketing': { width: 900, height: 640 },
                'media-library': { width: 920, height: 660 },
                'analytics': { width: 900, height: 620 },
                'learning-plan': { width: 760, height: 600 },
                'first-100': { width: 900, height: 650 },
                'inbox': { width: 760, height: 560 },
                'search': { width: 760, height: 520 },
                'tasks': { width: 800, height: 600 },
                'wallet': { width: 760, height: 560 },
                'orders': { width: 900, height: 640 },
                'bookings': { width: 900, height: 640 },
                'settings': { width: 860, height: 620 },
                'automations': { width: 860, height: 620 },
                'activity': { width: 820, height: 600 },
            };

            const readGeometryState = () => {
                if (typeof window.localStorage === 'undefined') return {};
                try {
                    const raw = window.localStorage.getItem(geometryStorageKey);
                    return raw ? JSON.parse(raw) : {};
                } catch (error) {
                    return {};
                }
            };

            const writeGeometryState = (state) => {
                if (typeof window.localStorage === 'undefined') return;
                try {
                    window.localStorage.setItem(geometryStorageKey, JSON.stringify(state));
                } catch (error) {
                    // Ignore storage failures.
                }
            };

            const iconMarkup = (icon) => {
                switch (icon) {
                    case 'cap':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9L12 4L21 9L12 14L3 9Z"></path><path d="M7 11V16L12 19L17 16V11"></path></svg>`;
                    case 'tray':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9H20L18 17H6L4 9Z"></path><path d="M9 13H15"></path></svg>`;
                    case 'spark':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3L13.8 8.2L19 10L13.8 11.8L12 17L10.2 11.8L5 10L10.2 8.2L12 3Z"></path><path d="M18.5 3.5L19.2 5.3L21 6L19.2 6.7L18.5 8.5L17.8 6.7L16 6L17.8 5.3L18.5 3.5Z"></path></svg>`;
                    case 'globe':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"></circle><path d="M4 12H20"></path><path d="M12 4C14.8 6.7 14.8 17.3 12 20"></path><path d="M12 4C9.2 6.7 9.2 17.3 12 20"></path></svg>`;
                    case 'pulse':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12H7L9.5 7L13.5 17L16 12H21"></path></svg>`;
                    case 'bag':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9H18L17 19H7L6 9Z"></path><path d="M9 9V7C9 5.3 10.3 4 12 4C13.7 4 15 5.3 15 7V9"></path></svg>`;
                    case 'image':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2"></rect><circle cx="9" cy="10" r="1.5"></circle><path d="M7 17L11.5 12.5L14.5 15.5L17 13L20 17"></path></svg>`;
                    case 'window':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2"></rect><path d="M4 9H20"></path><path d="M8 7H8.01"></path><path d="M11 7H11.01"></path></svg>`;
                    case 'checklist':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 7H18"></path><path d="M9 12H18"></path><path d="M9 17H18"></path><path d="M5 7L6.2 8.2L8 6.4"></path><path d="M5 12L6.2 13.2L8 11.4"></path><path d="M5 17L6.2 18.2L8 16.4"></path></svg>`;
                    case 'target':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="7"></circle><circle cx="12" cy="12" r="3.5"></circle><path d="M12 2V5"></path><path d="M22 12H19"></path></svg>`;
                    case 'megaphone':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12V9.5C4 8.7 4.7 8 5.5 8H8L16 5V19L8 16H5.5C4.7 16 4 15.3 4 14.5V12Z"></path><path d="M8 16L9.5 20"></path><path d="M18.5 9.5C19.5 10.2 20 11 20 12C20 13 19.5 13.8 18.5 14.5"></path></svg>`;
                    case 'search':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"></circle><path d="M20 20L16.5 16.5"></path></svg>`;
                    case 'gear':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15A1.7 1.7 0 0 0 19.7 16.8L19.8 17C20.1 17.6 20 18.3 19.5 18.7L18.7 19.5C18.3 20 17.6 20.1 17 19.8L16.8 19.7A1.7 1.7 0 0 0 15 19.4C14.4 19.6 14 20.2 14 20.8V21C14 21.6 13.6 22 13 22H11C10.4 22 10 21.6 10 21V20.8C10 20.2 9.6 19.6 9 19.4A1.7 1.7 0 0 0 7.2 19.7L7 19.8C6.4 20.1 5.7 20 5.3 19.5L4.5 18.7C4 18.3 3.9 17.6 4.2 17L4.3 16.8A1.7 1.7 0 0 0 4 15C3.8 14.4 3.2 14 2.6 14H2.4C1.8 14 1.4 13.6 1.4 13V11C1.4 10.4 1.8 10 2.4 10H2.6C3.2 10 3.8 9.6 4 9A1.7 1.7 0 0 0 3.7 7.2L3.6 7C3.3 6.4 3.4 5.7 3.9 5.3L4.7 4.5C5.1 4 5.8 3.9 6.4 4.2L6.6 4.3A1.7 1.7 0 0 0 8.4 4C9 3.8 9.4 3.2 9.4 2.6V2.4C9.4 1.8 9.8 1.4 10.4 1.4H12.4C13 1.4 13.4 1.8 13.4 2.4V2.6C13.4 3.2 13.8 3.8 14.4 4A1.7 1.7 0 0 0 16.2 3.7L16.4 3.6C17 3.3 17.7 3.4 18.1 3.9L18.9 4.7C19.4 5.1 19.5 5.8 19.2 6.4L19.1 6.6A1.7 1.7 0 0 0 19.4 8.4C19.6 9 20.2 9.4 20.8 9.4H21C21.6 9.4 22 9.8 22 10.4V12.4C22 13 21.6 13.4 21 13.4H20.8C20.2 13.4 19.6 13.8 19.4 14.4Z"></path></svg>`;
                    case 'chart':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 19V10"></path><path d="M12 19V6"></path><path d="M19 19V13"></path><path d="M4 19H20"></path></svg>`;
                    case 'wallet':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7.5C5 6.7 5.7 6 6.5 6H17.5C18.3 6 19 6.7 19 7.5V9H14.5C13.1 9 12 10.1 12 11.5C12 12.9 13.1 14 14.5 14H19V16.5C19 17.3 18.3 18 17.5 18H6.5C5.7 18 5 17.3 5 16.5V7.5Z"></path><path d="M19 9V14H14.5C13.7 14 13 13.3 13 12.5V10.5C13 9.7 13.7 9 14.5 9H19Z"></path></svg>`;
                    case 'box':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8L12 4L20 8L12 12L4 8Z"></path><path d="M4 8V16L12 20L20 16V8"></path><path d="M12 12V20"></path></svg>`;
                    case 'calendar-check':
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="6" width="16" height="14" rx="2"></rect><path d="M8 3V8"></path><path d="M16 3V8"></path><path d="M4 10H20"></path><path d="M9 15L11.2 17.2L15.5 12.9"></path></svg>`;
                    default:
                        return `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3H15L20 8V21H8C6.9 21 6 20.1 6 19V5C6 3.9 6.9 3 8 3Z"></path><path d="M15 3V8H20"></path></svg>`;
                }
            };

            const withEmbedParam = (url) => {
                try {
                    const target = new URL(url, window.location.origin);
                    target.searchParams.set('os_embed', '1');
                    return `${target.pathname}${target.search}${target.hash}`;
                } catch (error) {
                    return url.includes('?') ? `${url}&os_embed=1` : `${url}?os_embed=1`;
                }
            };

            const renderDock = () => {
                if (!dock) return;
                dock.innerHTML = '';

                Array.from(windows.values()).forEach((entry) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = `os-desktop-dock-item ${entry.minimized ? 'minimized' : ''} ${entry.el.classList.contains('active') && !entry.minimized ? 'active' : ''}`;
                    button.title = entry.app.label;
                    button.innerHTML = `<span class="os-dock-icon-tile ${entry.app.className || ''}">${iconMarkup(entry.app.icon)}</span>`;
                    button.addEventListener('click', () => {
                        if (entry.minimized) {
                            entry.minimized = false;
                            entry.el.classList.remove('is-minimized');
                            focusWindow(entry.app.key);
                        } else {
                            focusWindow(entry.app.key);
                        }
                        renderDock();
                    });
                    button.addEventListener('contextmenu', (event) => {
                        event.preventDefault();
                        entry.close();
                    });
                    dock.appendChild(button);
                });
            };

            const focusWindow = (key) => {
                const active = windows.get(key);
                if (!active) return;
                windows.forEach((entry) => entry.el.classList.remove('active'));
                zIndex += 1;
                active.el.style.zIndex = String(zIndex);
                active.el.classList.add('active');
                active.minimized = false;
                active.el.classList.remove('is-minimized');
                renderDock();
            };

            const getWindowPreset = (app) => {
                const preset = appPresets[app.key] || {};
                const maxWidth = Math.max(420, host.clientWidth - 48);
                const maxHeight = Math.max(320, host.clientHeight - 48);
                const saved = readGeometryState()[app.key] || {};
                return {
                    width: Math.min(saved.width || preset.width || 820, maxWidth),
                    height: Math.min(saved.height || preset.height || 600, maxHeight),
                };
            };

            const getLaunchPosition = (app, sourceNode = null) => {
                const preset = getWindowPreset(app);
                const count = windows.size;
                const col = count % 4;
                const row = Math.floor(count / 4);
                const maxX = Math.max(16, host.clientWidth - preset.width - 18);
                const maxY = Math.max(16, host.clientHeight - preset.height - 18);

                let nextX = 72 + (col * 54);
                let nextY = 88 + (row * 42);
                const saved = readGeometryState()[app.key] || null;

                if (saved && Number.isFinite(saved.left) && Number.isFinite(saved.top)) {
                    nextX = saved.left;
                    nextY = saved.top;
                } else if (sourceNode) {
                    const hostRect = host.getBoundingClientRect();
                    const sourceRect = sourceNode.getBoundingClientRect();
                    const sourceCenterX = sourceRect.left + (sourceRect.width / 2) - hostRect.left;
                    const sourceTopY = sourceRect.top - hostRect.top;
                    nextX = sourceCenterX - Math.min(170, preset.width / 2.4);
                    nextY = Math.max(24, sourceTopY - 28 + (row * 10));
                }

                return {
                    x: Math.min(Math.max(16, nextX), maxX),
                    y: Math.min(Math.max(16, nextY), maxY),
                };
            };

            const persistWindowGeometry = (entry) => {
                const state = readGeometryState();
                state[entry.app.key] = {
                    left: parseFloat(entry.el.style.left || '0'),
                    top: parseFloat(entry.el.style.top || '0'),
                    width: parseFloat(entry.el.style.width || '0'),
                    height: parseFloat(entry.el.style.height || '0'),
                };
                writeGeometryState(state);
            };

            const buildWindow = (app, startX, startY) => {
                const preset = getWindowPreset(app);
                const win = document.createElement('section');
                win.className = 'os-app-window active is-entering';
                win.dataset.windowKey = app.key;
                win.style.left = `${startX}px`;
                win.style.top = `${startY}px`;
                win.style.width = `${preset.width}px`;
                win.style.height = `${preset.height}px`;
                win.style.zIndex = String(++zIndex);
                win.innerHTML = `
                    <div class="os-app-window-bar" data-window-drag>
                        <div class="os-app-window-dots">
                            <button class="os-app-window-dot close" type="button" aria-label="Close ${app.label}"></button>
                            <button class="os-app-window-dot minimize" type="button" aria-label="Minimize ${app.label}"></button>
                            <button class="os-app-window-dot maximize" type="button" aria-label="Maximize ${app.label}"></button>
                        </div>
                        <div class="os-app-window-title">${app.label}</div>
                        <div class="os-app-window-actions"></div>
                    </div>
                    <iframe class="os-app-window-frame" title="${app.label}" src="${withEmbedParam(app.route)}"></iframe>
                `;
                host.appendChild(win);
                window.setTimeout(() => win.classList.remove('is-entering'), 280);

                const close = () => {
                    persistWindowGeometry(entry);
                    win.remove();
                    windows.delete(app.key);
                    renderDock();
                };

                const entry = {
                    app,
                    el: win,
                    minimized: false,
                    maximized: false,
                    previous: null,
                    close,
                };

                win.querySelector('.os-app-window-dot.close')?.addEventListener('click', close);
                win.querySelector('.os-app-window-dot.minimize')?.addEventListener('click', () => {
                    entry.minimized = true;
                    win.classList.add('is-minimized');
                    renderDock();
                });
                win.querySelector('.os-app-window-dot.maximize')?.addEventListener('click', () => {
                    if (!entry.maximized) {
                        entry.previous = {
                            left: win.style.left,
                            top: win.style.top,
                            width: win.style.width,
                            height: win.style.height,
                        };
                        win.style.left = '16px';
                        win.style.top = '16px';
                        win.style.width = `${Math.max(420, host.clientWidth - 32)}px`;
                        win.style.height = `${Math.max(320, host.clientHeight - 32)}px`;
                        entry.maximized = true;
                    } else if (entry.previous) {
                        win.style.left = entry.previous.left;
                        win.style.top = entry.previous.top;
                        win.style.width = entry.previous.width;
                        win.style.height = entry.previous.height;
                        entry.maximized = false;
                        entry.previous = null;
                    }
                    persistWindowGeometry(entry);
                    focusWindow(app.key);
                });
                win.addEventListener('mousedown', () => focusWindow(app.key));

                const dragHandle = win.querySelector('[data-window-drag]');
                let dragging = false;
                let originX = 0;
                let originY = 0;
                let baseX = 0;
                let baseY = 0;

                dragHandle?.addEventListener('mousedown', (event) => {
                    if (entry.maximized) return;
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
                    if (dragging) {
                        persistWindowGeometry(entry);
                    }
                    dragging = false;
                    document.body.style.userSelect = '';
                });

                windows.set(app.key, entry);
                focusWindow(app.key);
                renderDock();
            };

            const openApp = (key, sourceNode = null) => {
                const app = routeMap[key];
                if (!app) return;
                if (windows.has(key)) {
                    focusWindow(key);
                    return;
                }

                const launch = getLaunchPosition(app, sourceNode);
                buildWindow(app, launch.x, launch.y);
            };

            window.addEventListener('hatchers:open-app', (event) => {
                const key = event.detail?.key || '';
                if (key && routeMap[key]) {
                    openApp(key);
                    return;
                }

                const href = event.detail?.href || '';
                if (href) {
                    window.location.href = href;
                }
            });

            launcherNodes.forEach((node) => {
                node.addEventListener('click', (event) => {
                    if (node.dataset.dragMoved === '1') return;
                    event.preventDefault();
                    const key = node.dataset.launcherKey;
                    if (key) openApp(key, node);
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
