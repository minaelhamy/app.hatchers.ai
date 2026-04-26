@php
    /** @var \App\Models\Founder|null $sidebarFounder */
    $sidebarFounder = $founder ?? $dashboard['founder'] ?? auth()->user();
    $sidebarBusinessModel = strtolower(trim((string) ($businessModel ?? $sidebarFounder?->company?->business_model ?? 'hybrid')));
    $sidebarSupportsProducts = in_array($sidebarBusinessModel, ['product', 'hybrid'], true);
    $sidebarSupportsServices = in_array($sidebarBusinessModel, ['service', 'hybrid'], true);
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
    $sidebarItems = [
        ['key' => 'home', 'label' => 'Home', 'icon' => '⌂', 'href' => '/dashboard/founder'],
        ['key' => 'tasks', 'label' => 'Tasks', 'icon' => '◌', 'href' => route('founder.tasks')],
        ['key' => 'first-100', 'label' => 'First 100', 'icon' => '◎', 'href' => route('founder.first-100')],
        ['key' => 'marketing', 'label' => 'Marketing', 'icon' => '✎', 'href' => route('founder.marketing')],
        ['key' => 'website', 'label' => 'Website', 'icon' => '◧', 'href' => route('website')],
        ['key' => 'commerce', 'label' => 'Commerce', 'icon' => '⌁', 'href' => route('founder.commerce')],
        ['key' => 'wallet', 'label' => 'Wallet', 'icon' => '$', 'href' => route('founder.commerce.wallet')],
    ];

    if ($sidebarSupportsProducts) {
        $sidebarItems[] = ['key' => 'orders', 'label' => 'Orders', 'icon' => '▤', 'href' => route('founder.commerce.orders')];
    }

    if ($sidebarSupportsServices) {
        $sidebarItems[] = ['key' => 'bookings', 'label' => 'Bookings', 'icon' => '◫', 'href' => route('founder.commerce.bookings')];
    }

    $sidebarItems = array_merge($sidebarItems, [
        ['key' => 'activity', 'label' => 'Activity', 'icon' => '◔', 'href' => route('founder.activity')],
        ['key' => 'inbox', 'label' => 'Inbox', 'icon' => '✉', 'href' => route('founder.inbox')],
        ['key' => 'search', 'label' => 'Search', 'icon' => '⌕', 'href' => route('founder.search')],
        ['key' => 'ai-tools', 'label' => 'AI Tools', 'icon' => '✦', 'href' => route('founder.ai-tools')],
        ['key' => 'automations', 'label' => 'Automations', 'icon' => '↻', 'href' => route('founder.automations')],
        ['key' => 'analytics', 'label' => 'Analytics', 'icon' => '◒', 'href' => route('founder.analytics')],
        ['key' => 'media-library', 'label' => 'Media Library', 'icon' => '▥', 'href' => route('founder.media-library')],
        ['key' => 'pods', 'label' => 'Pods', 'icon' => '◍', 'href' => route('founder.pods')],
        ['key' => 'learning-plan', 'label' => 'Learning Plan', 'icon' => '▣', 'href' => route('founder.learning-plan')],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => '⚙', 'href' => route('founder.settings')],
    ]);
@endphp

<div class="{{ $sidebarInnerClass }}">
    <a class="{{ $sidebarBrandClass }}" href="/dashboard/founder">
        <img src="/brand/hatchers-ai-logo.png" alt="Hatchers AI">
    </a>
    <nav class="{{ $sidebarNavClass }}">
        @foreach ($sidebarItems as $item)
            <a class="{{ $sidebarItemClass }} {{ $sidebarActive === $item['key'] ? 'active' : '' }}" href="{{ $item['href'] }}">
                <span class="{{ $sidebarIconClass }}">{{ $item['icon'] }}</span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
</div>
<div class="{{ $sidebarFooterClass }}">
    <div class="{{ $sidebarUserClass }}">
        <div class="{{ $sidebarAvatarClass }}">{{ $sidebarInitial }}</div>
        <div>{{ $sidebarName !== '' ? $sidebarName : 'Founder' }}</div>
    </div>
    <form method="POST" action="/logout" style="margin:0;">
        @csrf
        <button class="{{ $sidebarIconClass }}" type="submit" style="border:0;background:transparent;cursor:pointer;">↘</button>
    </form>
</div>
