@php
    /** @var \App\Models\Founder|null $topbarFounder */
    $topbarFounder = $founder ?? $dashboard['founder'] ?? auth()->user();
    $topbarCompany = $company ?? $dashboard['company'] ?? $topbarFounder?->company;
    $topbarWorkspace = $workspace ?? ($dashboard['workspace'] ?? []);
    $topbarProjectName = trim((string) ($projectName ?? $topbarCompany?->company_name ?? 'Founder workspace'));
    $topbarSearchPlaceholder = $searchPlaceholder ?? ($topbarWorkspace['quick_prompt'] ?? 'Ask Hatchers what to build next...');
    $topbarNotificationCount = (int) ($notificationCount ?? count($topbarWorkspace['notifications'] ?? []));
    $topbarFounderName = trim((string) ($topbarFounder?->full_name ?? 'Founder'));
    $topbarFounderInitial = strtoupper(substr($topbarFounderName !== '' ? $topbarFounderName : 'F', 0, 1));
    $topbarSectionLabel = trim((string) ($sectionLabel ?? 'Workspace'));
@endphp

<div class="guidebook-topbar">
    <div class="guidebook-topbar-search">
        <span class="guidebook-topbar-search-icon">•</span>
        <span>{{ $topbarSearchPlaceholder }}</span>
        <span class="guidebook-topbar-kbd">⌘K</span>
    </div>
    <div class="guidebook-topbar-right">
        <a href="{{ route('founder.notifications') }}" class="guidebook-topbar-bell" aria-label="Notifications">
            <span>◌</span>
            @if ($topbarNotificationCount > 0)
                <span class="guidebook-topbar-badge">{{ $topbarNotificationCount }}</span>
            @endif
        </a>
        <a href="{{ route('founder.ai-tools') }}" class="guidebook-topbar-chip">
            <span class="guidebook-topbar-chip-label">AI Tools</span>
            <strong>{{ $topbarSectionLabel }}</strong>
        </a>
        <div class="guidebook-topbar-user">
            <div class="guidebook-topbar-avatar">{{ $topbarFounderInitial }}</div>
            <div class="guidebook-topbar-user-copy">
                <strong>{{ $topbarFounderName !== '' ? $topbarFounderName : 'Founder' }}</strong>
                <span>{{ $topbarProjectName }}</span>
            </div>
        </div>
        <form method="POST" action="/logout" style="margin:0;">
            @csrf
            <button type="submit" class="guidebook-topbar-logout">Logout</button>
        </form>
    </div>
</div>
