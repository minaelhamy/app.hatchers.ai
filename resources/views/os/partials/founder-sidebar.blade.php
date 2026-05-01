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
    $sidebarStorageKey = 'hatchers-os-launcher-order-' . ($sidebarFounder?->id ?? 'guest');
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
        ['key' => 'automations', 'label' => 'Automations', 'icon' => 'BOT', 'accent' => 'plum', 'href' => route('founder.coming-soon', ['feature' => 'automations'])],
        ['key' => 'affiliate-network', 'label' => 'Affiliate Network', 'icon' => 'AF', 'accent' => 'rose', 'href' => route('founder.coming-soon', ['feature' => 'affiliate-network'])],
        ['key' => 'offer-engineering', 'label' => 'Offer Engineering', 'icon' => 'OF', 'accent' => 'amber', 'href' => route('founder.coming-soon', ['feature' => 'offer-engineering'])],
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
@endphp

<div class="{{ $sidebarInnerClass }} os-launcher">
    <div class="os-launcher-header">
        <a class="{{ $sidebarBrandClass }} os-launcher-brand" href="/dashboard/founder">
            <img src="/brand/hatchers-ai-logo.png" alt="Hatchers AI">
        </a>
        <div class="os-launcher-status">
            <span>Hatchers AI OS</span>
            <strong>{{ $sidebarToday }}</strong>
        </div>
    </div>
    <div class="os-launcher-note">
        Drag icons to rearrange your workspace. Click any app to open it.
    </div>
    <nav class="{{ $sidebarNavClass }} os-launcher-nav" data-os-launcher data-storage-key="{{ $sidebarStorageKey }}">
        @foreach ($sidebarItems as $item)
            <a
                class="{{ $sidebarItemClass }} os-launcher-app {{ $sidebarActive === $item['key'] ? 'active' : '' }}"
                href="{{ $item['href'] }}"
                draggable="true"
                data-launcher-key="{{ $item['key'] }}"
                data-launcher-label="{{ $item['label'] }}"
                data-launcher-route="{{ $item['href'] }}"
                data-launcher-icon="{{ $item['icon'] }}"
            >
                <span class="os-launcher-app-surface tone-{{ $item['accent'] }}">
                    <span class="{{ $sidebarIconClass }} os-launcher-app-glyph">{{ $item['icon'] }}</span>
                </span>
                <span class="os-launcher-app-label">{{ $item['label'] }}</span>
                @if ($sidebarActive === $item['key'])
                    <span class="os-launcher-app-indicator"></span>
                @endif
            </a>
        @endforeach
    </nav>
</div>
<div class="{{ $sidebarFooterClass }} os-dock">
    <div class="os-dock-pins">
        @foreach ($sidebarDockItems as $item)
            <a
                class="os-dock-item {{ $sidebarActive === $item['key'] ? 'active' : '' }}"
                href="{{ $item['href'] }}"
                title="{{ $item['label'] }}"
                data-launcher-key="{{ $item['key'] }}"
                data-launcher-label="{{ $item['label'] }}"
                data-launcher-route="{{ $item['href'] }}"
                data-launcher-icon="{{ $item['icon'] }}"
            >
                <span class="os-dock-surface tone-{{ $item['accent'] }}">{{ $item['icon'] }}</span>
            </a>
        @endforeach
    </div>
    <div class="{{ $sidebarUserClass }} os-dock-user">
        <div class="{{ $sidebarAvatarClass }} os-dock-avatar">{{ $sidebarInitial }}</div>
        <div class="os-dock-user-copy">
            <strong>{{ $sidebarName !== '' ? $sidebarName : 'Founder' }}</strong>
            <span>Signed in</span>
        </div>
    </div>
    <form method="POST" action="/logout" style="margin:0;">
        @csrf
        <button class="os-dock-logout" type="submit" aria-label="Log out">↘</button>
    </form>
</div>
