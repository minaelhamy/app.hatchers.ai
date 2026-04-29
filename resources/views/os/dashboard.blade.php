@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page {
            padding: 0;
            font-family: "Inter", "Avenir Next", "Segoe UI", sans-serif;
        }

        .os-desktop-scene {
            position: relative;
            min-height: 100vh;
            padding: 28px 30px 48px;
            background:
                radial-gradient(circle at 78% 14%, rgba(230, 184, 188, 0.28), transparent 0 16%),
                linear-gradient(165deg, #ddd2c8 0%, #c8b8b0 100%);
            overflow: hidden;
        }

        .os-desktop-scene::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.11) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.11) 1px, transparent 1px);
            background-size: 88px 88px;
            opacity: 0.35;
            pointer-events: none;
        }

        .os-desktop-frame {
            position: relative;
            min-height: calc(100vh - 84px);
            border-radius: 22px;
            border: 1px solid rgba(209, 198, 187, 0.82);
            background:
                radial-gradient(circle at 78% 18%, rgba(234, 197, 201, 0.24), transparent 0 18%),
                linear-gradient(180deg, rgba(243, 236, 229, 0.92), rgba(234, 223, 214, 0.94));
            box-shadow:
                0 16px 40px rgba(71, 52, 31, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.56),
                inset 0 -60px 90px rgba(255, 255, 255, 0.08);
            overflow: hidden;
        }

        .os-desktop-frame::after {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 18% 20%, rgba(255, 255, 255, 0.14), transparent 0 22%),
                radial-gradient(circle at 84% 84%, rgba(196, 169, 150, 0.12), transparent 0 18%);
            pointer-events: none;
        }

        .os-desktop-bar {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: center;
            padding: 12px 18px 9px;
        }

        .os-desktop-bar::after {
            content: "";
            position: absolute;
            left: 20px;
            right: 20px;
            bottom: 0;
            height: 1px;
            background: linear-gradient(90deg, rgba(214, 200, 185, 0.05), rgba(214, 200, 185, 0.44), rgba(214, 200, 185, 0.05));
        }

        .os-desktop-bar-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .os-desktop-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: rgba(55, 44, 38, 0.92);
            font-family: "Inter Tight", "Inter", "Avenir Next", sans-serif;
            font-weight: 600;
            letter-spacing: -0.01em;
            flex-shrink: 0;
        }

        .os-desktop-brand-mark {
            width: 20px;
            height: 20px;
            border-radius: 6px;
            background: linear-gradient(135deg, #e11d74, #ef4444);
            box-shadow: 0 8px 22px rgba(225, 29, 116, 0.25);
            flex-shrink: 0;
        }

        .os-desktop-brand-name {
            font-size: 0.9rem;
        }

        .os-desktop-search {
            display: flex;
            align-items: center;
            gap: 10px;
            width: min(610px, 100%);
            min-height: 38px;
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid rgba(246, 240, 234, 0.92);
            background: rgba(255, 248, 243, 0.74);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.82),
                0 4px 12px rgba(80, 58, 40, 0.05);
            color: rgba(94, 82, 75, 0.7);
            backdrop-filter: blur(10px);
        }

        .os-desktop-search:hover {
            background: rgba(255, 250, 246, 0.8);
        }

        .os-desktop-search svg {
            width: 16px;
            height: 16px;
            opacity: 0.7;
            flex-shrink: 0;
        }

        .os-desktop-search-text {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.84rem;
            letter-spacing: -0.01em;
        }

        .os-desktop-shortcut {
            padding: 3px 8px;
            border-radius: 8px;
            border: 1px solid rgba(219, 208, 198, 0.72);
            background: rgba(255, 255, 255, 0.34);
            font-size: 0.7rem;
            color: rgba(118, 104, 93, 0.72);
        }

        .os-desktop-bar-right {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            justify-self: end;
        }

        .os-desktop-time {
            font-size: 0.82rem;
            font-weight: 500;
            color: rgba(70, 57, 48, 0.8);
            white-space: nowrap;
            letter-spacing: -0.01em;
        }

        .os-desktop-logout-form {
            margin: 0;
        }

        .os-desktop-logout {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 36px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid rgba(228, 216, 204, 0.95);
            background: rgba(255, 251, 247, 0.62);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.84),
                0 8px 18px rgba(80, 58, 40, 0.06);
            color: rgba(73, 58, 49, 0.86);
            font: inherit;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.16s ease, box-shadow 0.16s ease, background 0.16s ease;
        }

        .os-desktop-notifications {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 999px;
            border: 1px solid rgba(228, 216, 204, 0.95);
            background: rgba(255, 251, 247, 0.62);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.84),
                0 8px 18px rgba(80, 58, 40, 0.06);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: rgba(73, 58, 49, 0.86);
            text-decoration: none;
            transition: transform 0.16s ease, box-shadow 0.16s ease, background 0.16s ease;
        }

        .os-desktop-notifications:hover {
            transform: translateY(-1px);
            background: rgba(255, 253, 250, 0.78);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.9),
                0 12px 22px rgba(80, 58, 40, 0.1);
        }

        .os-desktop-notifications svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .os-desktop-notifications-badge {
            position: absolute;
            top: -3px;
            right: -2px;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ef4444, #f97316);
            color: #fff;
            font-size: 0.62rem;
            font-weight: 800;
            line-height: 1;
            box-shadow: 0 8px 14px rgba(214, 69, 38, 0.24);
        }

        .os-desktop-logout:hover {
            transform: translateY(-1px);
            background: rgba(255, 253, 250, 0.78);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.9),
                0 12px 22px rgba(80, 58, 40, 0.1);
        }

        .os-desktop-logout svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .os-desktop-icons {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: repeat(4, 116px);
            gap: 24px 28px;
            padding: 56px 0 92px 52px;
            align-content: start;
        }

        .os-desktop-icon {
            display: grid;
            justify-items: center;
            gap: 10px;
            text-decoration: none;
            color: rgba(51, 40, 35, 0.92);
            user-select: none;
            cursor: pointer;
            transition: transform 0.18s ease;
        }

        .os-desktop-icon.dragging {
            opacity: 0.45;
            transform: scale(0.97);
        }

        .os-desktop-icon.drop-target .os-desktop-icon-tile {
            transform: translateY(-3px);
            box-shadow:
                0 18px 34px rgba(70, 54, 42, 0.18),
                0 0 0 2px rgba(255, 255, 255, 0.35);
        }

        .os-desktop-icon-tile {
            width: 82px;
            height: 82px;
            border-radius: 20px;
            display: grid;
            place-items: center;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow:
                0 14px 28px rgba(61, 46, 28, 0.12),
                inset 0 1px 0 rgba(255, 255, 255, 0.12);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            overflow: hidden;
        }

        .os-desktop-icon-tile::before {
            content: "";
            position: absolute;
            inset: 1px;
            border-radius: inherit;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.18), rgba(255, 255, 255, 0.02));
            opacity: 0.85;
            pointer-events: none;
        }

        .os-desktop-icon-tile::after {
            content: "";
            position: absolute;
            top: 8px;
            left: 12px;
            right: 12px;
            height: 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            filter: blur(8px);
            pointer-events: none;
        }

        .os-desktop-icon:hover .os-desktop-icon-tile {
            transform: translateY(-2px);
            box-shadow: 0 18px 34px rgba(61, 46, 28, 0.16);
        }

        .os-desktop-icon:hover {
            transform: translateY(-1px);
        }

        .os-desktop-icon-label {
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: -0.01em;
            text-align: center;
            line-height: 1.16;
            max-width: 96px;
            color: rgba(60, 47, 39, 0.88);
        }

        .os-desktop-icon svg {
            width: 34px;
            height: 34px;
            stroke: #fff;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
            position: relative;
            z-index: 1;
        }

        .os-icon-learning { background: linear-gradient(180deg, #7d8dbc, #6977a1); }
        .os-icon-inbox { background: linear-gradient(180deg, #9aa0af, #7f8597); }
        .os-icon-brand { background: linear-gradient(180deg, #707382, #5f6272); }
        .os-icon-ai { background: linear-gradient(180deg, #ed4177, #df4d53); }
        .os-icon-activity { background: linear-gradient(180deg, #a992a1, #948195); }
        .os-icon-commerce { background: linear-gradient(180deg, #b69c78, #a18b69); }
        .os-icon-media { background: linear-gradient(180deg, #8ba0b8, #7489a2); }
        .os-icon-website { background: linear-gradient(180deg, #8f9d8d, #798976); }
        .os-icon-tasks { background: linear-gradient(180deg, #9a8ca4, #877a93); }
        .os-icon-growth { background: linear-gradient(180deg, #ef77b0, #da5d94); }
        .os-icon-marketing { background: linear-gradient(180deg, #f08f63, #db7250); }
        .os-icon-search { background: linear-gradient(180deg, #8495a6, #6f8091); }
        .os-icon-automation { background: linear-gradient(180deg, #7ea19a, #668a83); }
        .os-icon-analytics { background: linear-gradient(180deg, #7f93be, #657dad); }
        .os-icon-wallet { background: linear-gradient(180deg, #c2a067, #ad8d5c); }
        .os-icon-orders { background: linear-gradient(180deg, #c48e6f, #ae7658); }
        .os-icon-bookings { background: linear-gradient(180deg, #9a85bf, #846ca9); }

        .os-desktop-footnote {
            position: absolute;
            right: 34px;
            bottom: 28px;
            z-index: 2;
            color: rgba(106, 90, 82, 0.5);
            font-size: 0.72rem;
            letter-spacing: 0.05em;
            text-transform: lowercase;
        }

        .os-desktop-icon.is-heartbeating .os-desktop-icon-tile {
            animation: os-heartbeat-tile 1.7s ease-in-out infinite;
        }

        .os-desktop-icon.is-heartbeating .os-desktop-icon-label {
            animation: os-heartbeat-label 1.7s ease-in-out infinite;
        }

        @keyframes os-heartbeat-tile {
            0%, 100% {
                transform: scale(1);
                box-shadow:
                    0 14px 28px rgba(61, 46, 28, 0.12),
                    inset 0 1px 0 rgba(255, 255, 255, 0.12);
            }
            28% {
                transform: scale(1.035);
                box-shadow:
                    0 20px 36px rgba(61, 46, 28, 0.18),
                    0 0 0 8px rgba(255, 255, 255, 0.12),
                    inset 0 1px 0 rgba(255, 255, 255, 0.12);
            }
            46% {
                transform: scale(0.995);
            }
            62% {
                transform: scale(1.02);
            }
        }

        @keyframes os-heartbeat-label {
            0%, 100% { color: rgba(60, 47, 39, 0.88); }
            28% { color: rgba(31, 23, 18, 0.98); }
        }

        .os-desktop-dock {
            position: absolute;
            left: 50%;
            bottom: 18px;
            transform: translateX(-50%);
            z-index: 9;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 20px;
            background: rgba(255, 248, 242, 0.46);
            border: 1px solid rgba(238, 228, 219, 0.95);
            box-shadow:
                0 18px 34px rgba(66, 51, 39, 0.12),
                inset 0 1px 0 rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(18px);
        }

        .os-desktop-dock:empty {
            display: none;
        }

        .os-desktop-dock-item {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            border: 1px solid rgba(229, 219, 208, 0.9);
            background: rgba(255, 255, 255, 0.42);
            box-shadow: 0 10px 18px rgba(66, 51, 39, 0.08);
            display: grid;
            place-items: center;
            cursor: pointer;
            position: relative;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            padding: 0;
        }

        .os-desktop-dock-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 22px rgba(66, 51, 39, 0.14);
        }

        .os-desktop-dock-item.active {
            transform: translateY(-3px);
            box-shadow: 0 18px 28px rgba(66, 51, 39, 0.18);
        }

        .os-desktop-dock-item.minimized::after,
        .os-desktop-dock-item.active::after {
            content: "";
            position: absolute;
            bottom: -8px;
            left: 50%;
            width: 8px;
            height: 8px;
            margin-left: -4px;
            border-radius: 999px;
            background: rgba(81, 65, 55, 0.6);
        }

        .os-dock-icon-tile {
            width: 38px;
            height: 38px;
            border-radius: 13px;
            display: grid;
            place-items: center;
            position: relative;
            overflow: hidden;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.14),
                0 8px 16px rgba(62, 47, 36, 0.16);
        }

        .os-dock-icon-tile::before {
            content: "";
            position: absolute;
            inset: 1px;
            border-radius: inherit;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.16), rgba(255, 255, 255, 0.03));
            pointer-events: none;
        }

        .os-desktop-dock-item svg {
            width: 20px;
            height: 20px;
            stroke: #fff;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        @media (max-width: 900px) {
            .os-desktop-scene {
                padding: 18px;
            }

            .os-desktop-frame {
                min-height: calc(100vh - 36px);
                border-radius: 18px;
            }

            .os-desktop-bar {
                grid-template-columns: 1fr;
            }

            .os-desktop-bar-left {
                flex-wrap: wrap;
            }

            .os-desktop-icons {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 24px 18px;
                padding: 36px 20px 72px;
            }
        }
    </style>
@endsection

@section('scripts')
    <script>
        (() => {
            const desktop = document.querySelector('[data-os-desktop-home]');
            if (!desktop) return;

            const founderId = @json((string) ($dashboard['founder']->id ?? 'guest'));
            const todayKey = @json(now()->timezone(config('app.timezone'))->format('Y-m-d'));
            const openedStorageKey = `hatchers-os-opened-icons-${founderId}`;
            const tasksBeatKey = `hatchers-os-tasks-heartbeat-${founderId}`;

            const readJson = (key) => {
                try {
                    return JSON.parse(window.localStorage.getItem(key) || '{}');
                } catch (error) {
                    return {};
                }
            };

            const writeJson = (key, value) => {
                try {
                    window.localStorage.setItem(key, JSON.stringify(value));
                } catch (error) {
                    // Ignore storage failures.
                }
            };

            const markOpened = (iconKey) => {
                if (!iconKey) return;
                const openedState = readJson(openedStorageKey);
                openedState[iconKey] = todayKey;
                writeJson(openedStorageKey, openedState);
            };

            const openedState = readJson(openedStorageKey);
            const tasksState = readJson(tasksBeatKey);

            desktop.querySelectorAll('.os-desktop-icon[data-launcher-key]').forEach((icon) => {
                const iconKey = icon.dataset.launcherKey || '';
                const needsHeartbeat = icon.dataset.launcherHeartbeat === '1';
                const needsDailyHeartbeat = icon.dataset.launcherDailyHeartbeat === '1';

                if (needsHeartbeat && openedState[iconKey] !== todayKey) {
                    icon.classList.add('is-heartbeating');
                }

                if (needsDailyHeartbeat && tasksState[todayKey] !== true) {
                    icon.classList.add('is-heartbeating');
                }

                icon.addEventListener('click', () => {
                    markOpened(iconKey);
                    icon.classList.remove('is-heartbeating');

                    if (needsDailyHeartbeat) {
                        const nextTasksState = readJson(tasksBeatKey);
                        nextTasksState[todayKey] = true;
                        writeJson(tasksBeatKey, nextTasksState);
                    }
                });
            });
        })();
    </script>
@endsection

@section('content')
    @php
        $workspace = $dashboard['workspace'] ?? [];
        $launchCards = collect($launchCards ?? [])->keyBy(fn ($card) => strtolower((string) ($card['module'] ?? '')));
        $desktopOpen = request('open', '');
        $desktopNow = now()->timezone(config('app.timezone'));
        $desktopClock = $desktopNow->format('D, M j   g:i A');
        $desktopDateKey = $desktopNow->format('Y-m-d');
        $businessModel = strtolower((string) ($dashboard['company']->business_model ?? 'hybrid'));
        $supportsProducts = in_array($businessModel, ['product', 'hybrid'], true);
        $supportsServices = in_array($businessModel, ['service', 'hybrid'], true);
        $companyIntelligenceWizard = $companyIntelligenceWizard ?? ['is_complete' => true];
        $companyIntelligenceComplete = (bool) ($companyIntelligenceWizard['is_complete'] ?? true);
        $unreadNotificationCount = (int) ($workspace['unread_notification_count'] ?? 0);
        $desktopApps = [
            [
                'key' => 'atlas-engine',
                'label' => 'Atlas',
                'route' => (string) ($launchCards->get('atlas')['url'] ?? route('workspace.launch', ['module' => 'atlas'])),
                'class' => 'os-icon-ai',
                'icon' => 'globe',
                'external' => !empty($launchCards->get('atlas')['url']),
            ],
            [
                'key' => 'lms-engine',
                'label' => 'LMS',
                'route' => (string) ($launchCards->get('lms')['url'] ?? route('founder.learning-plan')),
                'class' => 'os-icon-learning',
                'icon' => 'cap',
                'external' => !empty($launchCards->get('lms')['url']),
            ],
            [
                'key' => 'bazaar-engine',
                'label' => 'Bazaar',
                'route' => (string) ($launchCards->get('bazaar')['url'] ?? route('founder.commerce')),
                'class' => 'os-icon-commerce',
                'icon' => 'bag',
                'external' => !empty($launchCards->get('bazaar')['url']),
            ],
            [
                'key' => 'servio-engine',
                'label' => 'Servio',
                'route' => (string) ($launchCards->get('servio')['url'] ?? route('website')),
                'class' => 'os-icon-website',
                'icon' => 'window',
                'external' => !empty($launchCards->get('servio')['url']),
            ],
            [
                'key' => 'learning-plan',
                'label' => 'Learning Hub',
                'route' => route('founder.learning-plan'),
                'class' => 'os-icon-learning',
                'icon' => 'cap',
                'external' => false,
            ],
            [
                'key' => 'inbox',
                'label' => 'Inbox',
                'route' => route('founder.inbox'),
                'class' => 'os-icon-inbox',
                'icon' => 'tray',
                'external' => false,
            ],
            [
                'key' => 'settings',
                'label' => 'Company Intelligence',
                'route' => route('founder.settings', ['step' => 'basics']),
                'class' => 'os-icon-automation',
                'icon' => 'gear',
                'external' => false,
                'heartbeat' => !$companyIntelligenceComplete,
            ],
            [
                'key' => 'atlas-brand-studio',
                'label' => 'Brand Studio',
                'route' => route('founder.settings'),
                'class' => 'os-icon-brand',
                'icon' => 'spark',
                'external' => false,
            ],
            [
                'key' => 'atlas-campaign-studio',
                'label' => 'Campaign Studio',
                'route' => route('workspace.launch', ['module' => 'atlas', 'target' => '/ai-images']),
                'class' => 'os-icon-marketing',
                'icon' => 'image',
                'external' => true,
            ],
            [
                'key' => 'atlas-agents',
                'label' => 'Atlas Agents',
                'route' => route('workspace.launch', ['module' => 'atlas', 'target' => '/ai-chat-bots']),
                'class' => 'os-icon-ai',
                'icon' => 'user',
                'external' => true,
            ],
            [
                'key' => 'media-library',
                'label' => 'Media Library',
                'route' => route('founder.media-library'),
                'class' => 'os-icon-media',
                'icon' => 'image',
                'external' => false,
            ],
            [
                'key' => 'tasks',
                'label' => 'Tasks',
                'route' => route('founder.tasks'),
                'class' => 'os-icon-tasks',
                'icon' => 'checklist',
                'external' => false,
                'daily_heartbeat' => true,
            ],
            [
                'key' => 'first-100',
                'label' => 'Lead Tracker',
                'route' => route('founder.first-100'),
                'class' => 'os-icon-growth',
                'icon' => 'target',
                'external' => false,
            ],
            [
                'key' => 'search',
                'label' => 'Search',
                'route' => route('founder.search'),
                'class' => 'os-icon-search',
                'icon' => 'search',
                'external' => false,
            ],
            [
                'key' => 'automations',
                'label' => 'Automations',
                'route' => route('founder.automations'),
                'class' => 'os-icon-automation',
                'icon' => 'gear',
                'external' => false,
            ],
            [
                'key' => 'analytics',
                'label' => 'Analytics',
                'route' => route('founder.analytics'),
                'class' => 'os-icon-analytics',
                'icon' => 'chart',
                'external' => false,
            ],
            [
                'key' => 'wallet',
                'label' => 'Wallet',
                'route' => route('founder.commerce.wallet'),
                'class' => 'os-icon-wallet',
                'icon' => 'wallet',
                'external' => false,
            ],
        ];

        if ($supportsProducts) {
            $desktopApps[] = [
                'key' => 'orders',
                'label' => 'Orders',
                'route' => route('founder.commerce.orders'),
                'class' => 'os-icon-orders',
                'icon' => 'box',
                'external' => false,
            ];
        }

        if ($supportsServices) {
            $desktopApps[] = [
                'key' => 'bookings',
                'label' => 'Bookings',
                'route' => route('founder.commerce.bookings'),
                'class' => 'os-icon-bookings',
                'icon' => 'calendar-check',
                'external' => false,
            ];
        }
    @endphp

    <div class="os-desktop-scene" data-os-desktop-home data-os-open="{{ e((string) $desktopOpen) }}">
        <div class="os-desktop-frame">
            <div class="os-desktop-bar">
                <div class="os-desktop-bar-left">
                    <a class="os-desktop-brand" href="/dashboard/founder">
                        <span class="os-desktop-brand-mark"></span>
                        <span class="os-desktop-brand-name">Hatchers</span>
                    </a>
                    <div class="os-desktop-search">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="11" cy="11" r="6.5"></circle>
                            <path d="M16 16L21 21"></path>
                        </svg>
                        <span class="os-desktop-search-text">What would you like to do?</span>
                        <span class="os-desktop-shortcut">⌘K</span>
                    </div>
                </div>
                <div class="os-desktop-bar-right">
                    <a class="os-desktop-notifications" href="{{ route('founder.notifications') }}" aria-label="Notifications">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 4C9.8 4 8 5.8 8 8V10.1C8 11 7.7 11.8 7.1 12.5L5.8 14C5.2 14.6 5.6 15.5 6.4 15.5H17.6C18.4 15.5 18.8 14.6 18.2 14L16.9 12.5C16.3 11.8 16 11 16 10.1V8C16 5.8 14.2 4 12 4Z"></path>
                            <path d="M10 18C10.4 19.1 11.1 19.6 12 19.6C12.9 19.6 13.6 19.1 14 18"></path>
                        </svg>
                        @if ($unreadNotificationCount > 0)
                            <span class="os-desktop-notifications-badge">{{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}</span>
                        @endif
                    </a>
                    <div class="os-desktop-time">{{ $desktopClock }}</div>
                    <form class="os-desktop-logout-form" method="POST" action="/logout">
                        @csrf
                        <button class="os-desktop-logout" type="submit" aria-label="Log out">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M10 17L15 12L10 7"></path>
                                <path d="M15 12H4"></path>
                                <path d="M20 20H12"></path>
                                <path d="M20 4H12"></path>
                            </svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="os-desktop-icons" data-os-launcher data-storage-key="hatchers-os-desktop-order-{{ $dashboard['founder']->id ?? 'guest' }}">
                @foreach ($desktopApps as $app)
                    <a
                        class="os-desktop-icon"
                        href="{{ $app['route'] }}"
                        draggable="true"
                        data-launcher-key="{{ $app['key'] }}"
                        data-launcher-label="{{ $app['label'] }}"
                        data-launcher-route="{{ $app['route'] }}"
                        data-launcher-class="{{ $app['class'] }}"
                        data-launcher-icon="{{ $app['icon'] }}"
                        data-launcher-external="{{ !empty($app['external']) ? '1' : '0' }}"
                        data-launcher-heartbeat="{{ !empty($app['heartbeat']) ? '1' : '0' }}"
                        data-launcher-daily-heartbeat="{{ !empty($app['daily_heartbeat']) ? '1' : '0' }}"
                    >
                        <span class="os-desktop-icon-tile {{ $app['class'] }}">
                            @switch($app['icon'])
                                @case('cap')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M3 9L12 4L21 9L12 14L3 9Z"></path>
                                        <path d="M7 11V16L12 19L17 16V11"></path>
                                    </svg>
                                    @break
                                @case('tray')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M4 9H20L18 17H6L4 9Z"></path>
                                        <path d="M9 13H15"></path>
                                    </svg>
                                    @break
                                @case('sun')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <circle cx="12" cy="12" r="3.2"></circle>
                                        <path d="M12 4V2"></path>
                                        <path d="M12 22V20"></path>
                                        <path d="M4 12H2"></path>
                                        <path d="M22 12H20"></path>
                                        <path d="M6.3 6.3L4.9 4.9"></path>
                                        <path d="M19.1 19.1L17.7 17.7"></path>
                                        <path d="M17.7 6.3L19.1 4.9"></path>
                                        <path d="M4.9 19.1L6.3 17.7"></path>
                                    </svg>
                                    @break
                                @case('spark')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M12 3L13.8 8.2L19 10L13.8 11.8L12 17L10.2 11.8L5 10L10.2 8.2L12 3Z"></path>
                                        <path d="M18.5 3.5L19.2 5.3L21 6L19.2 6.7L18.5 8.5L17.8 6.7L16 6L17.8 5.3L18.5 3.5Z"></path>
                                    </svg>
                                    @break
                                @case('globe')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <circle cx="12" cy="12" r="8"></circle>
                                        <path d="M4 12H20"></path>
                                        <path d="M12 4C14.8 6.7 14.8 17.3 12 20"></path>
                                        <path d="M12 4C9.2 6.7 9.2 17.3 12 20"></path>
                                    </svg>
                                    @break
                                @case('calendar')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <rect x="4" y="6" width="16" height="14" rx="2"></rect>
                                        <path d="M8 3V8"></path>
                                        <path d="M16 3V8"></path>
                                        <path d="M4 10H20"></path>
                                    </svg>
                                    @break
                                @case('calendar-check')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <rect x="4" y="6" width="16" height="14" rx="2"></rect>
                                        <path d="M8 3V8"></path>
                                        <path d="M16 3V8"></path>
                                        <path d="M4 10H20"></path>
                                        <path d="M9 15L11.2 17.2L15.5 12.9"></path>
                                    </svg>
                                    @break
                                @case('bag')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M6 9H18L17 19H7L6 9Z"></path>
                                        <path d="M9 9V7C9 5.3 10.3 4 12 4C13.7 4 15 5.3 15 7V9"></path>
                                    </svg>
                                    @break
                                @case('file')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M8 3H15L20 8V21H8C6.9 21 6 20.1 6 19V5C6 3.9 6.9 3 8 3Z"></path>
                                        <path d="M15 3V8H20"></path>
                                    </svg>
                                    @break
                                @case('image')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <rect x="4" y="5" width="16" height="14" rx="2"></rect>
                                        <circle cx="9" cy="10" r="1.5"></circle>
                                        <path d="M7 17L11.5 12.5L14.5 15.5L17 13L20 17"></path>
                                    </svg>
                                    @break
                                @case('gear')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M19.4 15A1.7 1.7 0 0 0 19.7 16.8L19.8 17C20.1 17.6 20 18.3 19.5 18.7L18.7 19.5C18.3 20 17.6 20.1 17 19.8L16.8 19.7A1.7 1.7 0 0 0 15 19.4C14.4 19.6 14 20.2 14 20.8V21C14 21.6 13.6 22 13 22H11C10.4 22 10 21.6 10 21V20.8C10 20.2 9.6 19.6 9 19.4A1.7 1.7 0 0 0 7.2 19.7L7 19.8C6.4 20.1 5.7 20 5.3 19.5L4.5 18.7C4 18.3 3.9 17.6 4.2 17L4.3 16.8A1.7 1.7 0 0 0 4 15C3.8 14.4 3.2 14 2.6 14H2.4C1.8 14 1.4 13.6 1.4 13V11C1.4 10.4 1.8 10 2.4 10H2.6C3.2 10 3.8 9.6 4 9A1.7 1.7 0 0 0 3.7 7.2L3.6 7C3.3 6.4 3.4 5.7 3.9 5.3L4.7 4.5C5.1 4 5.8 3.9 6.4 4.2L6.6 4.3A1.7 1.7 0 0 0 8.4 4C9 3.8 9.4 3.2 9.4 2.6V2.4C9.4 1.8 9.8 1.4 10.4 1.4H12.4C13 1.4 13.4 1.8 13.4 2.4V2.6C13.4 3.2 13.8 3.8 14.4 4A1.7 1.7 0 0 0 16.2 3.7L16.4 3.6C17 3.3 17.7 3.4 18.1 3.9L18.9 4.7C19.4 5.1 19.5 5.8 19.2 6.4L19.1 6.6A1.7 1.7 0 0 0 19.4 8.4C19.6 9 20.2 9.4 20.8 9.4H21C21.6 9.4 22 9.8 22 10.4V12.4C22 13 21.6 13.4 21 13.4H20.8C20.2 13.4 19.6 13.8 19.4 14.4Z"></path>
                                    </svg>
                                    @break
                                @case('window')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <rect x="4" y="5" width="16" height="14" rx="2"></rect>
                                        <path d="M4 9H20"></path>
                                        <path d="M8 7H8.01"></path>
                                        <path d="M11 7H11.01"></path>
                                    </svg>
                                    @break
                                @case('checklist')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M9 7H18"></path>
                                        <path d="M9 12H18"></path>
                                        <path d="M9 17H18"></path>
                                        <path d="M5 7L6.2 8.2L8 6.4"></path>
                                        <path d="M5 12L6.2 13.2L8 11.4"></path>
                                        <path d="M5 17L6.2 18.2L8 16.4"></path>
                                    </svg>
                                    @break
                                @case('target')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <circle cx="12" cy="12" r="7"></circle>
                                        <circle cx="12" cy="12" r="3.5"></circle>
                                        <path d="M12 2V5"></path>
                                        <path d="M22 12H19"></path>
                                    </svg>
                                    @break
                                @case('megaphone')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M4 12V9.5C4 8.7 4.7 8 5.5 8H8L16 5V19L8 16H5.5C4.7 16 4 15.3 4 14.5V12Z"></path>
                                        <path d="M8 16L9.5 20"></path>
                                        <path d="M18.5 9.5C19.5 10.2 20 11 20 12C20 13 19.5 13.8 18.5 14.5"></path>
                                    </svg>
                                    @break
                                @case('search')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <circle cx="11" cy="11" r="6"></circle>
                                        <path d="M20 20L16.5 16.5"></path>
                                    </svg>
                                    @break
                                @case('chart')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M5 19V10"></path>
                                        <path d="M12 19V6"></path>
                                        <path d="M19 19V13"></path>
                                        <path d="M4 19H20"></path>
                                    </svg>
                                    @break
                                @case('wallet')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M5 7.5C5 6.7 5.7 6 6.5 6H17.5C18.3 6 19 6.7 19 7.5V9H14.5C13.1 9 12 10.1 12 11.5C12 12.9 13.1 14 14.5 14H19V16.5C19 17.3 18.3 18 17.5 18H6.5C5.7 18 5 17.3 5 16.5V7.5Z"></path>
                                        <path d="M19 9V14H14.5C13.7 14 13 13.3 13 12.5V10.5C13 9.7 13.7 9 14.5 9H19Z"></path>
                                    </svg>
                                    @break
                                @case('box')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M4 8L12 4L20 8L12 12L4 8Z"></path>
                                        <path d="M4 8V16L12 20L20 16V8"></path>
                                        <path d="M12 12V20"></path>
                                    </svg>
                                    @break
                                @case('pulse')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M3 12H7L9.5 7L13.5 17L16 12H21"></path>
                                    </svg>
                                    @break
                                @case('user')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <circle cx="12" cy="8" r="3.3"></circle>
                                        <path d="M6 20C6.8 16.9 9 15.3 12 15.3C15 15.3 17.2 16.9 18 20"></path>
                                    </svg>
                                    @break
                            @endswitch
                        </span>
                        <span class="os-desktop-icon-label">{{ $app['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <div class="os-desktop-footnote">Tap an icon to open · drag to rearrange</div>
            <div class="os-desktop-dock" data-os-desktop-dock></div>
            <div class="os-window-host" data-os-window-host></div>
        </div>
    </div>
@endsection
