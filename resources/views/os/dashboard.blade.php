@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page {
            padding: 0;
        }

        .os-desktop-scene {
            position: relative;
            min-height: 100vh;
            padding: 42px 44px 56px;
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
            border-radius: 24px;
            border: 1px solid rgba(209, 198, 187, 0.82);
            background:
                radial-gradient(circle at 78% 18%, rgba(234, 197, 201, 0.24), transparent 0 18%),
                linear-gradient(180deg, rgba(243, 236, 229, 0.92), rgba(234, 223, 214, 0.94));
            box-shadow:
                0 16px 40px rgba(71, 52, 31, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.56);
            overflow: hidden;
        }

        .os-desktop-bar {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 18px;
            align-items: center;
            padding: 14px 20px 12px;
        }

        .os-desktop-bar-left {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 0;
        }

        .os-desktop-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: rgba(55, 44, 38, 0.92);
            font-weight: 700;
            letter-spacing: -0.01em;
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
            font-size: 1rem;
        }

        .os-desktop-search {
            display: flex;
            align-items: center;
            gap: 10px;
            width: min(580px, 100%);
            padding: 8px 16px;
            border-radius: 999px;
            border: 1px solid rgba(246, 240, 234, 0.92);
            background: rgba(255, 248, 243, 0.62);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
            color: rgba(94, 82, 75, 0.7);
            backdrop-filter: blur(10px);
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
            font-size: 0.98rem;
        }

        .os-desktop-shortcut {
            padding: 3px 8px;
            border-radius: 8px;
            border: 1px solid rgba(219, 208, 198, 0.72);
            background: rgba(255, 255, 255, 0.34);
            font-size: 0.8rem;
        }

        .os-desktop-time {
            font-size: 0.94rem;
            font-weight: 500;
            color: rgba(62, 50, 43, 0.86);
            white-space: nowrap;
        }

        .os-desktop-icons {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: repeat(3, 124px);
            gap: 34px 42px;
            padding: 78px 0 72px 74px;
        }

        .os-desktop-icon {
            display: grid;
            justify-items: center;
            gap: 12px;
            text-decoration: none;
            color: rgba(51, 40, 35, 0.92);
            user-select: none;
            cursor: pointer;
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
            width: 90px;
            height: 90px;
            border-radius: 24px;
            display: grid;
            place-items: center;
            box-shadow: 0 16px 28px rgba(61, 46, 28, 0.12);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .os-desktop-icon:hover .os-desktop-icon-tile {
            transform: translateY(-2px);
            box-shadow: 0 18px 34px rgba(61, 46, 28, 0.16);
        }

        .os-desktop-icon-label {
            font-size: 0.88rem;
            font-weight: 500;
            letter-spacing: -0.01em;
        }

        .os-desktop-icon svg {
            width: 38px;
            height: 38px;
            stroke: #fff;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .os-icon-lms { background: linear-gradient(180deg, #7d8dbc, #6977a1); }
        .os-icon-inbox { background: linear-gradient(180deg, #9aa0af, #7f8597); }
        .os-icon-settings { background: linear-gradient(180deg, #707382, #5f6272); }
        .os-icon-atlas { background: linear-gradient(180deg, #ed4177, #df4d53); }
        .os-icon-calendar { background: linear-gradient(180deg, #a992a1, #948195); }
        .os-icon-bazaar { background: linear-gradient(180deg, #b69c78, #a18b69); }
        .os-icon-files { background: linear-gradient(180deg, #8ba0b8, #7489a2); }
        .os-icon-servio { background: linear-gradient(180deg, #8f9d8d, #798976); }
        .os-icon-profile { background: linear-gradient(180deg, #9a8ca4, #877a93); }

        .os-desktop-footnote {
            position: absolute;
            right: 34px;
            bottom: 28px;
            z-index: 2;
            color: rgba(106, 90, 82, 0.56);
            font-size: 0.82rem;
            letter-spacing: 0.08em;
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
            background: rgba(255, 248, 242, 0.52);
            border: 1px solid rgba(232, 221, 211, 0.9);
            box-shadow:
                0 16px 32px rgba(66, 51, 39, 0.12),
                inset 0 1px 0 rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
        }

        .os-desktop-dock:empty {
            display: none;
        }

        .os-desktop-dock-item {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            border: 1px solid rgba(229, 219, 208, 0.9);
            background: rgba(255, 255, 255, 0.76);
            box-shadow: 0 10px 18px rgba(66, 51, 39, 0.08);
            display: grid;
            place-items: center;
            cursor: pointer;
            position: relative;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .os-desktop-dock-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 22px rgba(66, 51, 39, 0.14);
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

        .os-desktop-dock-item svg {
            width: 24px;
            height: 24px;
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
                gap: 28px 18px;
                padding: 36px 20px 72px;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $workspace = $dashboard['workspace'] ?? [];
        $desktopOpen = request('open', '');
        $desktopNow = now()->timezone(config('app.timezone'));
        $desktopClock = $desktopNow->format('D, M j   g:i A');
        $businessModel = strtolower((string) ($dashboard['company']->business_model ?? 'hybrid'));
        $supportsProducts = in_array($businessModel, ['product', 'hybrid'], true);
        $supportsServices = in_array($businessModel, ['service', 'hybrid'], true);
        $desktopApps = [
            [
                'key' => 'learning-plan',
                'label' => 'Learning Hub',
                'route' => route('founder.learning-plan'),
                'class' => 'os-icon-lms',
                'icon' => 'cap',
            ],
            [
                'key' => 'inbox',
                'label' => 'Inbox',
                'route' => route('founder.inbox'),
                'class' => 'os-icon-inbox',
                'icon' => 'tray',
            ],
            [
                'key' => 'settings',
                'label' => 'Brand Studio',
                'route' => route('founder.settings'),
                'class' => 'os-icon-settings',
                'icon' => 'sun',
            ],
            [
                'key' => 'ai-tools',
                'label' => 'AI Studio',
                'route' => route('founder.ai-tools'),
                'class' => 'os-icon-atlas',
                'icon' => 'globe',
            ],
            [
                'key' => 'activity',
                'label' => 'Activity',
                'route' => route('founder.activity'),
                'class' => 'os-icon-calendar',
                'icon' => 'calendar',
            ],
            [
                'key' => 'commerce',
                'label' => 'Commerce',
                'route' => route('founder.commerce'),
                'class' => 'os-icon-bazaar',
                'icon' => 'bag',
            ],
            [
                'key' => 'media-library',
                'label' => 'Media Library',
                'route' => route('founder.media-library'),
                'class' => 'os-icon-files',
                'icon' => 'file',
            ],
            [
                'key' => 'website',
                'label' => 'Website Studio',
                'route' => route('website'),
                'class' => 'os-icon-servio',
                'icon' => 'gear',
            ],
            [
                'key' => 'tasks',
                'label' => 'Tasks',
                'route' => route('founder.tasks'),
                'class' => 'os-icon-profile',
                'icon' => 'user',
            ],
            [
                'key' => 'first-100',
                'label' => 'First 100',
                'route' => route('founder.first-100'),
                'class' => 'os-icon-atlas',
                'icon' => 'globe',
            ],
            [
                'key' => 'marketing',
                'label' => 'Marketing',
                'route' => route('founder.marketing'),
                'class' => 'os-icon-calendar',
                'icon' => 'calendar',
            ],
            [
                'key' => 'search',
                'label' => 'Search',
                'route' => route('founder.search'),
                'class' => 'os-icon-inbox',
                'icon' => 'tray',
            ],
            [
                'key' => 'automations',
                'label' => 'Automations',
                'route' => route('founder.automations'),
                'class' => 'os-icon-settings',
                'icon' => 'gear',
            ],
            [
                'key' => 'analytics',
                'label' => 'Analytics',
                'route' => route('founder.analytics'),
                'class' => 'os-icon-bazaar',
                'icon' => 'bag',
            ],
            [
                'key' => 'wallet',
                'label' => 'Wallet',
                'route' => route('founder.commerce.wallet'),
                'class' => 'os-icon-servio',
                'icon' => 'file',
            ],
        ];

        if ($supportsProducts) {
            $desktopApps[] = [
                'key' => 'orders',
                'label' => 'Orders',
                'route' => route('founder.commerce.orders'),
                'class' => 'os-icon-bazaar',
                'icon' => 'bag',
            ];
        }

        if ($supportsServices) {
            $desktopApps[] = [
                'key' => 'bookings',
                'label' => 'Bookings',
                'route' => route('founder.commerce.bookings'),
                'class' => 'os-icon-calendar',
                'icon' => 'calendar',
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
                <div class="os-desktop-time">{{ $desktopClock }}</div>
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
                                @case('gear')
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M19.4 15A1.7 1.7 0 0 0 19.7 16.8L19.8 17C20.1 17.6 20 18.3 19.5 18.7L18.7 19.5C18.3 20 17.6 20.1 17 19.8L16.8 19.7A1.7 1.7 0 0 0 15 19.4C14.4 19.6 14 20.2 14 20.8V21C14 21.6 13.6 22 13 22H11C10.4 22 10 21.6 10 21V20.8C10 20.2 9.6 19.6 9 19.4A1.7 1.7 0 0 0 7.2 19.7L7 19.8C6.4 20.1 5.7 20 5.3 19.5L4.5 18.7C4 18.3 3.9 17.6 4.2 17L4.3 16.8A1.7 1.7 0 0 0 4 15C3.8 14.4 3.2 14 2.6 14H2.4C1.8 14 1.4 13.6 1.4 13V11C1.4 10.4 1.8 10 2.4 10H2.6C3.2 10 3.8 9.6 4 9A1.7 1.7 0 0 0 3.7 7.2L3.6 7C3.3 6.4 3.4 5.7 3.9 5.3L4.7 4.5C5.1 4 5.8 3.9 6.4 4.2L6.6 4.3A1.7 1.7 0 0 0 8.4 4C9 3.8 9.4 3.2 9.4 2.6V2.4C9.4 1.8 9.8 1.4 10.4 1.4H12.4C13 1.4 13.4 1.8 13.4 2.4V2.6C13.4 3.2 13.8 3.8 14.4 4A1.7 1.7 0 0 0 16.2 3.7L16.4 3.6C17 3.3 17.7 3.4 18.1 3.9L18.9 4.7C19.4 5.1 19.5 5.8 19.2 6.4L19.1 6.6A1.7 1.7 0 0 0 19.4 8.4C19.6 9 20.2 9.4 20.8 9.4H21C21.6 9.4 22 9.8 22 10.4V12.4C22 13 21.6 13.4 21 13.4H20.8C20.2 13.4 19.6 13.8 19.4 14.4Z"></path>
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
