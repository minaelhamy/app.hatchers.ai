@php
    /** @var \App\Models\Founder|null $sidebarFounder */
    $sidebarFounder = $founder ?? $dashboard['founder'] ?? auth()->user();
    $sidebarBusinessModel = strtolower(trim((string) ($businessModel ?? $sidebarFounder?->company?->business_model ?? 'hybrid')));
    $sidebarSupportsProducts = in_array($sidebarBusinessModel, ['product', 'hybrid'], true);
    $sidebarNavClass = $navClass ?? 'founder-nav';
    $sidebarItemClass = $itemClass ?? 'founder-nav-item';
    $sidebarIconClass = $iconClass ?? 'founder-nav-icon';
    $sidebarInnerClass = $innerClass ?? 'founder-sidebar-inner';
    $sidebarBrandClass = $brandClass ?? 'founder-brand';
    $sidebarFooterClass = $footerClass ?? 'founder-sidebar-footer';
    $sidebarUserClass = $userClass ?? 'founder-user';
    $sidebarAvatarClass = $avatarClass ?? 'founder-avatar';
    $sidebarActive = $activeKey ?? 'home';
    $sidebarName = trim((string) ($sidebarFounder?->full_name ?? 'Founder'));
    $sidebarInitial = strtoupper(substr($sidebarName !== '' ? $sidebarName : 'F', 0, 1));
    $sidebarToday = now()->timezone(config('app.timezone'))->format('D j M');
    $sidebarItems = [
        ['key' => 'home', 'label' => 'Home', 'icon' => 'HM', 'accent' => 'amber', 'href' => '/dashboard/founder'],
        ['key' => 'tasks', 'label' => 'Tasks', 'icon' => 'TK', 'accent' => 'sky', 'href' => route('founder.tasks')],
        ['key' => 'first-100', 'label' => 'Lead Tracker', 'icon' => '100', 'accent' => 'rose', 'href' => route('founder.first-100')],
        ['key' => 'marketing', 'label' => 'Marketing', 'icon' => 'MK', 'accent' => 'plum', 'href' => route('founder.marketing')],
        ['key' => 'website', 'label' => 'Website', 'icon' => 'WEB', 'accent' => 'mint', 'href' => route('website')],
        ['key' => 'commerce', 'label' => 'Commerce', 'icon' => 'COM', 'accent' => 'slate', 'href' => route('founder.commerce')],
        ['key' => 'wallet', 'label' => 'Wallet', 'icon' => 'WL', 'accent' => 'gold', 'href' => route('founder.commerce.wallet')],
    ];

    if ($sidebarSupportsProducts) {
        $sidebarItems[] = ['key' => 'orders', 'label' => 'Orders', 'icon' => 'ORD', 'accent' => 'copper', 'href' => route('founder.commerce.orders')];
    }

    $sidebarItems = array_merge($sidebarItems, [
        ['key' => 'inbox', 'label' => 'Inbox', 'icon' => 'IN', 'accent' => 'sky', 'href' => route('founder.inbox')],
        ['key' => 'search', 'label' => 'Search', 'icon' => 'Q', 'accent' => 'amber', 'href' => route('founder.search')],
        ['key' => 'ai-tools', 'label' => 'AI Studio', 'icon' => 'AI', 'accent' => 'rose', 'href' => route('founder.ai-tools')],
        ['key' => 'automations', 'label' => 'Automations (Coming Soon)', 'icon' => 'BOT', 'accent' => 'plum', 'href' => 'javascript:void(0)', 'disabled' => true],
        ['key' => 'affiliate-network', 'label' => 'Affiliate Network (Coming Soon)', 'icon' => 'AF', 'accent' => 'rose', 'href' => 'javascript:void(0)', 'disabled' => true],
        ['key' => 'offer-engineering', 'label' => 'Offer Engineering (Coming Soon)', 'icon' => 'OF', 'accent' => 'amber', 'href' => 'javascript:void(0)', 'disabled' => true],
        ['key' => 'analytics', 'label' => 'Analytics', 'icon' => 'AN', 'accent' => 'mint', 'href' => route('founder.analytics')],
        ['key' => 'media-library', 'label' => 'Media Library', 'icon' => 'ML', 'accent' => 'copper', 'href' => route('founder.media-library')],
        ['key' => 'learning-plan', 'label' => 'Learning Plan', 'icon' => 'LP', 'accent' => 'gold', 'href' => route('founder.learning-plan')],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => 'SYS', 'accent' => 'slate', 'href' => route('founder.settings')],
    ]);

    $sidebarDockKeys = ['home', 'ai-tools', 'website', 'commerce', 'marketing'];
    $sidebarDockItems = collect($sidebarItems)
        ->filter(fn ($item) => in_array($item['key'], $sidebarDockKeys, true))
        ->values()
        ->all();
    $sidebarPrimaryItems = collect($sidebarItems)
        ->filter(fn ($item) => in_array($item['key'], ['home', 'tasks', 'marketing', 'website', 'commerce', 'inbox', 'ai-tools'], true))
        ->values()
        ->all();
    $sidebarSupportItems = collect($sidebarItems)
        ->reject(fn ($item) => in_array($item['key'], ['home', 'tasks', 'marketing', 'website', 'commerce', 'inbox', 'ai-tools'], true))
        ->values()
        ->all();
@endphp

<div class="{{ $sidebarInnerClass }} os-launcher guidebook-sidepane">
    <div class="guidebook-sidepane-head">
        <a class="{{ $sidebarBrandClass }} guidebook-sidepane-brand" href="/dashboard/founder">
            <span class="guidebook-sidepane-mark"></span>
            <span class="guidebook-sidepane-brand-copy">
                <strong>Hatchers AI OS</strong>
                <span>{{ $sidebarToday }}</span>
            </span>
        </a>
    </div>

    <div class="guidebook-sidepane-segment">
        <span class="guidebook-sidepane-segment-button">
            <span class="guidebook-sidepane-segment-icon">◫</span>
            <span>Browse</span>
        </span>
        <span class="guidebook-sidepane-segment-button is-active">
            <span class="guidebook-sidepane-segment-icon">✦</span>
            <span>Agent</span>
            <span class="guidebook-sidepane-badge">NEW</span>
        </span>
    </div>

    <div class="guidebook-sidepane-search">
        <span class="guidebook-sidepane-search-icon">⌕</span>
        <span>Search workspaces...</span>
    </div>

    <div class="guidebook-sidepane-section-label">Core</div>
    <nav class="{{ $sidebarNavClass }} guidebook-sidepane-nav">
        @foreach ($sidebarPrimaryItems as $item)
            <a
                class="{{ $sidebarItemClass }} guidebook-sidepane-item {{ $sidebarActive === $item['key'] ? 'active' : '' }} {{ !empty($item['disabled']) ? 'is-disabled' : '' }}"
                href="{{ $item['href'] }}"
                data-launcher-disabled="{{ !empty($item['disabled']) ? '1' : '0' }}"
                aria-disabled="{{ !empty($item['disabled']) ? 'true' : 'false' }}"
            >
                <span class="guidebook-sidepane-item-copy">
                    <span class="{{ $sidebarIconClass }} guidebook-sidepane-item-icon">{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                </span>
                @if ($sidebarActive === $item['key'])
                    <span class="guidebook-sidepane-item-indicator"></span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="guidebook-sidepane-section-label">Support</div>
    <nav class="{{ $sidebarNavClass }} guidebook-sidepane-nav guidebook-sidepane-nav--compact">
        @foreach ($sidebarSupportItems as $item)
            <a
                class="{{ $sidebarItemClass }} guidebook-sidepane-item {{ $sidebarActive === $item['key'] ? 'active' : '' }} {{ !empty($item['disabled']) ? 'is-disabled' : '' }}"
                href="{{ $item['href'] }}"
                data-launcher-disabled="{{ !empty($item['disabled']) ? '1' : '0' }}"
                aria-disabled="{{ !empty($item['disabled']) ? 'true' : 'false' }}"
            >
                <span class="guidebook-sidepane-item-copy">
                    <span class="{{ $sidebarIconClass }} guidebook-sidepane-item-icon">{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                </span>
                @if ($sidebarActive === $item['key'])
                    <span class="guidebook-sidepane-item-indicator"></span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="guidebook-sidepane-spacer"></div>
</div>
<div class="{{ $sidebarFooterClass }} os-dock guidebook-sidepane-footer">
    <div class="guidebook-sidepane-quick-label">Quick access</div>
    <div class="os-dock-pins guidebook-sidepane-pins">
        @foreach ($sidebarDockItems as $item)
            <a
                class="os-dock-item guidebook-sidepane-pin {{ $sidebarActive === $item['key'] ? 'active' : '' }}"
                href="{{ $item['href'] }}"
                title="{{ $item['label'] }}"
            >
                <span class="os-dock-surface guidebook-sidepane-pin-surface tone-{{ $item['accent'] }}">{{ $item['icon'] }}</span>
            </a>
        @endforeach
    </div>
    <div class="{{ $sidebarUserClass }} os-dock-user guidebook-sidepane-user">
        <div class="{{ $sidebarAvatarClass }} os-dock-avatar guidebook-sidepane-user-avatar">{{ $sidebarInitial }}</div>
        <div class="os-dock-user-copy guidebook-sidepane-user-copy">
            <strong>{{ $sidebarName !== '' ? $sidebarName : 'Founder' }}</strong>
            <span>{{ $sidebarFounder?->email ?: 'Signed in' }}</span>
        </div>
    </div>
    <form method="POST" action="/logout" style="margin:0;">
        @csrf
        <button class="os-dock-logout guidebook-sidepane-logout" type="submit" aria-label="Log out">Logout</button>
    </form>
</div>
