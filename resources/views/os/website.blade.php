@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .workspace-shell { min-height:100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 240px; background:#f8f5ee; }
        .workspace-sidebar, .workspace-rightbar { background:rgba(255,252,247,0.82); border-color:var(--line); border-style:solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .workspace-rightbar { border-width:0 0 0 1px; background:rgba(255,251,246,0.92); }
        .workspace-sidebar-inner, .workspace-rightbar-inner { padding:22px 18px; }
        .workspace-brand { display:inline-block; margin-bottom:24px; }
        .workspace-brand img { width:168px; height:auto; display:block; }
        .workspace-nav { display:grid; gap:6px; }
        .workspace-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .workspace-nav-item.active { background:#ece6db; }
        .workspace-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .workspace-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .workspace-user { display:flex; align-items:center; gap:10px; }
        .workspace-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .workspace-main { padding:26px 28px 28px; }
        .workspace-main-inner { max-width:980px; margin:0 auto; }
        .workspace-rightbar h3 { font-size:0.83rem; letter-spacing:0.06em; text-transform:uppercase; color:var(--muted); margin-bottom:12px; }
        .workspace-rail-list { display:grid; gap:10px; }
        .workspace-rail-item { background:rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:14px; padding:12px 14px; }
        .workspace-stage-nav { display:flex; gap:10px; flex-wrap:wrap; margin-top:18px; }
        .workspace-stage-tab { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; text-decoration:none; color:var(--ink); background:rgba(255,255,255,0.88); border:1px solid rgba(220,207,191,0.8); font-weight:600; }
        .workspace-stage-tab.active { background:#ece6db; }
        .workspace-stage-helper { margin-top:14px; padding:14px 16px; border-radius:16px; background:rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.7); color:var(--muted); }
        .website-build-loading {
            min-height: 460px;
            display: grid;
            place-items: center;
            text-align: center;
            border-radius: 28px;
            border: 1px solid rgba(216, 202, 186, 0.78);
            background: rgba(255, 252, 248, 0.92);
            box-shadow: 0 24px 60px rgba(96, 78, 59, 0.08);
            padding: 28px;
        }
        .website-build-loading-mark {
            width: 104px;
            height: 104px;
            border-radius: 28px;
            margin: 0 auto 18px;
            background: linear-gradient(180deg, #ff4f7a 0%, #ff6a5c 100%);
            box-shadow: 0 18px 40px rgba(255, 95, 108, 0.28);
            position: relative;
        }
        .website-build-loading-mark::after {
            content: "";
            position: absolute;
            inset: -10px;
            border-radius: 34px;
            border: 1px solid rgba(255, 120, 132, 0.28);
            animation: websiteBuildPulse 1.8s ease-out infinite;
        }
        .website-build-loading h2 {
            margin: 0 0 10px;
            font-size: clamp(2rem, 3.5vw, 3rem);
            letter-spacing: -0.05em;
            line-height: 1;
        }
        .website-build-loading p {
            margin: 0;
            color: #746657;
            font-size: 1rem;
            line-height: 1.7;
            max-width: 560px;
        }
        @keyframes websiteBuildPulse {
            0% { transform: scale(0.92); opacity: 0.75; }
            70% { transform: scale(1.18); opacity: 0; }
            100% { transform: scale(1.18); opacity: 0; }
        }
        @media (max-width:1240px) { .workspace-shell { grid-template-columns:220px 1fr; } .workspace-rightbar { display:none; } }
        @media (max-width:900px) { .workspace-shell { grid-template-columns:1fr; } .workspace-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .workspace-sidebar-footer { display:none; } .workspace-main { padding:20px 16px 24px; } }
    </style>
@endsection

@section('content')
    @php
        $founder = auth()->user();
        $osEmbedMode = request()->boolean('os_embed');
        $companyName = $website['company_name'];
        $businessModel = $website['business_model'];
        $websiteStatus = $website['website_status'];
        $recommendedEngine = $website['recommended_engine'];
        $currentWebsiteUrl = $website['current_website_url'];
        $recommendedSubdomain = $website['recommended_subdomain'];
        $websitePath = $website['website_path'];
        $customDomainExample = $website['custom_domain_example'];
        $customDomain = $website['custom_domain'];
        $customDomainStatus = $website['custom_domain_status'];
        $engines = $website['engines'];
        $themeOptions = $website['theme_options'];
        $domainModel = $website['domain_model'];
        $nextSteps = $website['next_steps'];
        $generationStatus = $website['website_generation_status'];
        $buildBrief = $website['build_brief'] ?? [];
        $buildSummary = $buildBrief['company_intelligence_summary'] ?? [];
        $buildIntake = $buildBrief['intake'] ?? [];
        $buildMissingItems = $buildBrief['missing_items'] ?? [];
        $autopilot = $website['autopilot'] ?? [];
        $autopilotDraft = $autopilot['draft'] ?? null;
        $launchSystem = $autopilot['launch_system'] ?? null;
        $recommendedCard = collect($engines)->firstWhere('key', $recommendedEngine)
            ?: ($engines[0] ?? [
                'key' => $recommendedEngine ?: 'servio',
                'label' => strtoupper($recommendedEngine ?: 'servio'),
                'role' => 'Website engine',
                'theme' => '1',
                'website_title' => $companyName,
                'website_mode' => $businessModel === 'product' ? 'product' : 'service',
            ]);
        $initialThemeOptions = $themeOptions[$recommendedEngine] ?? [];
        $dnsTargets = $website['dns_targets'];
        $supportsProducts = in_array($businessModel, ['product', 'hybrid'], true);
        $supportsServices = in_array($businessModel, ['service', 'hybrid'], true);
        $defaultWebsiteTitle = old('website_title', $autopilotDraft['title'] ?? $recommendedCard['website_title']);
        $defaultThemeTemplate = old('theme_template', $autopilotDraft['theme_template'] ?? $recommendedCard['theme']);
        $buildOfferCards = collect(old('offer_titles', []))->isNotEmpty()
            ? collect(old('offer_titles', []))->map(function ($title, $index) {
                return [
                    'title' => (string) $title,
                    'price' => (string) (old('offer_prices', [])[$index] ?? ''),
                    'description' => (string) (old('offer_descriptions', [])[$index] ?? ''),
                ];
            })->all()
            : (is_array($buildIntake['offer_cards'] ?? null) ? $buildIntake['offer_cards'] : []);
        $buildOfferCards = array_pad(array_slice($buildOfferCards, 0, 4), 4, ['title' => '', 'price' => '', 'description' => '']);
        $trustPointInputs = old('trust_points', is_array($buildIntake['trust_points_list'] ?? null) ? $buildIntake['trust_points_list'] : []);
        $trustPointInputs = array_pad(array_slice($trustPointInputs, 0, 6), 6, '');
        $faqQuestionInputs = old('faq_questions', is_array($buildIntake['faq_questions_list'] ?? null) ? $buildIntake['faq_questions_list'] : []);
        $faqQuestionInputs = array_pad(array_slice($faqQuestionInputs, 0, 6), 6, '');
        $pageSectionInputs = old('page_sections', is_array($buildIntake['page_sections'] ?? null) ? $buildIntake['page_sections'] : []);
        $socialProfiles = is_array($buildIntake['social_profiles'] ?? null) ? $buildIntake['social_profiles'] : [];
        $socialDefaults = [];
        foreach ($socialProfiles as $profile) {
            $network = strtolower((string) ($profile['network'] ?? ''));
            $url = (string) ($profile['url'] ?? '');
            $field = match ($network) {
                'instagram' => 'instagram_url',
                'facebook' => 'facebook_url',
                'tiktok' => 'tiktok_url',
                'linkedin' => 'linkedin_url',
                'youtube' => 'youtube_url',
                'website' => 'website_url',
                default => null,
            };
            if ($field) {
                $socialDefaults[$field] = $url;
            }
        }
        $imageDirection = is_array($buildIntake['image_direction'] ?? null) ? $buildIntake['image_direction'] : [];
        $imageSubjects = old('image_subjects', is_array($imageDirection['subjects'] ?? null) ? $imageDirection['subjects'] : []);
        $avoidVisuals = old('avoid_visuals', is_array($imageDirection['avoid'] ?? null) ? $imageDirection['avoid'] : []);
        $pageSectionOptions = [
            'hero' => 'Hero',
            'about' => 'About / Story',
            'offers' => 'Offers',
            'services' => 'Services',
            'products' => 'Products',
            'pricing' => 'Pricing',
            'results' => 'Results',
            'testimonials' => 'Testimonials',
            'faq' => 'FAQ',
            'contact' => 'Contact',
            'gallery' => 'Gallery',
        ];
        $imageStyleOptions = ['Clean and minimal', 'Bold and modern', 'Dark and moody', 'Warm and human', 'Luxury and premium', 'Playful and colorful'];
        $imageMoodOptions = ['Trustworthy', 'Energetic', 'Calm', 'Confident', 'Authentic', 'High-end'];
        $imageSubjectOptions = ['Founder portraits', 'Team at work', 'Behind the scenes', 'Customers using the offer', 'Products close-up', 'Location or local scenes', 'Process or transformation', 'Live performance or action shots'];
        $avoidVisualOptions = ['Generic laptops', 'Corporate handshakes', 'Charts and dashboards', 'Fake office meetings', 'Over-polished stock poses', 'Countdown / hype visuals', 'Random lifestyle photos'];
        $websiteStage = request()->query('stage', 'build');
        if (!in_array($websiteStage, ['build', 'overview', 'setup', 'publish'], true)) {
            $websiteStage = 'build';
        }
        $stageHelp = [
            'build' => 'Start here after Company Intelligence. Hatchers will show the company brief, collect any missing website details, and build the first site for you.',
            'overview' => 'Start here if you are launching for the first time. Review the draft, make sure the message feels right, then move to setup.',
            'setup' => 'Set the public name, page path, theme, and first offer. This is the main build step before launch.',
            'publish' => 'Use this step when the draft and setup are ready and you want the public OS site to go live.',
        ];
    @endphp

    <div class="workspace-shell" @if($osEmbedMode) style="grid-template-columns:minmax(0,1fr);" @endif>
        @unless($osEmbedMode)
        <aside class="workspace-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $businessModel,
                'activeKey' => 'website',
                'navClass' => 'workspace-nav',
                'itemClass' => 'workspace-nav-item',
                'iconClass' => 'workspace-nav-icon',
                'innerClass' => 'workspace-sidebar-inner',
                'brandClass' => 'workspace-brand',
                'footerClass' => 'workspace-sidebar-footer',
                'userClass' => 'workspace-user',
                'avatarClass' => 'workspace-avatar',
            ])
        </aside>
        @endunless

        <main class="workspace-main">
            <div class="workspace-main-inner">
            @if (session('success'))
                <section class="card" style="margin-bottom: 22px; border-color: rgba(44, 122, 87, 0.35);">
                    <strong>Success</strong>
                    <p class="muted" style="margin-top: 8px;">{{ session('success') }}</p>
                </section>
            @endif

            @if (session('error'))
                <section class="card" style="margin-bottom: 22px; border-color: rgba(179, 34, 83, 0.35);">
                    <strong>Could not complete that step</strong>
                    <p class="muted" style="margin-top: 8px;">{{ session('error') }}</p>
                </section>
            @endif

            <section class="hero">
                <div class="eyebrow">Website Launch</div>
                <h1>Build the first website for {{ $companyName }}.</h1>
                <p class="muted">
                    Review the draft, finish the setup, and publish when you're ready.
                </p>
                <div class="cta-row">
                    <span class="pill">Business model: {{ ucfirst($businessModel) }}</span>
                    <span class="pill">Site status: {{ ucfirst(str_replace('_', ' ', $websiteStatus)) }}</span>
                    <span class="pill">Launch draft: {{ ucfirst(str_replace('_', ' ', $generationStatus)) }}</span>
                </div>
                <div class="workspace-stage-nav">
                    <a class="workspace-stage-tab {{ $websiteStage === 'build' ? 'active' : '' }}" href="{{ route('website', array_filter(['stage' => 'build', 'os_embed' => $osEmbedMode ? 1 : null])) }}">1. Build My Website</a>
                    <a class="workspace-stage-tab {{ $websiteStage === 'overview' ? 'active' : '' }}" href="{{ route('website', array_filter(['stage' => 'overview', 'os_embed' => $osEmbedMode ? 1 : null])) }}">2. Review Draft</a>
                    <a class="workspace-stage-tab {{ $websiteStage === 'setup' ? 'active' : '' }}" href="{{ route('website', array_filter(['stage' => 'setup', 'os_embed' => $osEmbedMode ? 1 : null])) }}">3. Finish Setup</a>
                    <a class="workspace-stage-tab {{ $websiteStage === 'publish' ? 'active' : '' }}" href="{{ route('website', array_filter(['stage' => 'publish', 'os_embed' => $osEmbedMode ? 1 : null])) }}">4. Publish</a>
                </div>
                <div class="workspace-stage-helper">{{ $stageHelp[$websiteStage] }}</div>
            </section>

            @if ($websiteStage === 'build' && $generationStatus === 'in_progress')
            <section class="website-build-loading" style="margin-top: 22px;">
                <div>
                    <div class="website-build-loading-mark" aria-hidden="true"></div>
                    <div class="eyebrow" style="margin-bottom:8px;">Website Build In Progress</div>
                    <h2>Working on your website</h2>
                    <p>We are preparing your first website draft now. You can close this window and we will notify you as soon as it is ready.</p>
                </div>
            </section>
            @elseif ($websiteStage === 'build')
            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Company Intelligence Brief</h2>
                    <p class="muted" style="margin-bottom: 14px;">This information will shape the first website draft. You can still refine it later.</p>
                    <div class="stack">
                        @foreach ($buildSummary as $row)
                            @if (!empty($row['value']))
                                <div class="stack-item">
                                    <strong>{{ $row['label'] }}</strong><br>
                                    <span class="muted">{{ $row['value'] }}</span>
                                </div>
                            @endif
                        @endforeach
                        @if (!empty($buildMissingItems))
                            <div class="stack-item">
                                <strong>Helpful details still missing</strong><br>
                                @foreach ($buildMissingItems as $missing)
                                    <div class="muted">• {{ $missing }}</div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <h2>Build My Website</h2>
                    <p class="muted" style="margin-bottom: 14px;">Hatchers will build, fill, and publish the first version for you. We use your company intelligence, then create the services, pricing, offer stack, FAQ, blog, media direction, and website content in the background.</p>
                    <form method="POST" action="{{ route('website.build.store') }}" class="stack">
                        @csrf
                        @if($osEmbedMode)
                            <input type="hidden" name="os_embed" value="1">
                        @endif
                        <div class="stack-item">
                            <strong>What should this website do first?</strong><br>
                            <input type="text" name="website_goal" value="{{ old('website_goal', $buildIntake['website_goal'] ?? '') }}" placeholder="Get customers, book discovery calls, sell a starter offer, collect leads..." style="margin-top:10px;width:100%;padding:12px;border-radius:14px;border:1px solid var(--line);background:#fff;">
                        </div>
                        <div class="stack-item">
                            <strong>Write more about yourself</strong><br>
                            <span class="muted">Tell us the personal story, credibility, background, or philosophy we should use to make the site feel real and trustworthy.</span>
                            <textarea name="founder_story_notes" rows="5" placeholder="Example: I rebuilt my business after losing momentum, I care about authenticity over hype, clients trust me because..." style="margin-top:10px;width:100%;padding:12px;border-radius:14px;border:1px solid var(--line);background:#fff;">{{ old('founder_story_notes', $buildIntake['founder_story_notes'] ?? '') }}</textarea>
                        </div>
                        <div class="stack-item">
                            <strong>Write more about your services and pricing</strong><br>
                            <span class="muted">If you know your offers, put them here in any format. Hatchers will turn them into proper services, pricing, FAQ, and sales copy.</span>
                            <textarea name="services_pricing_notes" rows="6" placeholder="Example: Discovery Call | Free | 20-minute fit call&#10;Signature Package | $497 | Best for clients who need...&#10;Ongoing Support | $997/mo | Includes..." style="margin-top:10px;width:100%;padding:12px;border-radius:14px;border:1px solid var(--line);background:#fff;">{{ old('services_pricing_notes', $buildIntake['services_pricing_notes'] ?? '') }}</textarea>
                        </div>
                        <div class="stack-item">
                            <strong>Anything we should absolutely include or avoid?</strong><br>
                            <span class="muted">Use this for brand direction, image mood, must-mention stories, or anything sensitive. We’ll handle the rest.</span>
                            <textarea name="special_requests" rows="4" placeholder="Example: Make it feel bold and premium, lead with our origin story, avoid stock-photo corporate vibes, keep the CTA focused on discovery calls." style="margin-top:10px;width:100%;padding:12px;border-radius:14px;border:1px solid var(--line);background:#fff;">{{ old('special_requests', $buildIntake['special_requests'] ?? '') }}</textarea>
                        </div>
                        <button class="btn primary" type="submit">Build And Publish My Website</button>
                    </form>
                </div>
            </section>
            @endif

            @if ($websiteStage === 'overview')
            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Draft Review</h2>
                    <p class="muted" style="margin-bottom: 14px;">Hatchers uses the founder brief, ICP, and vertical blueprint to draft the first site structure before the founder starts editing.</p>
                    @if ($autopilotDraft)
                        <div class="stack">
                            <div class="stack-item">
                                <strong>{{ $autopilotDraft['title'] ?: $companyName }}</strong><br>
                                <span class="muted">{{ $autopilotDraft['hero']['headline'] ?? 'First website draft ready' }}</span>
                            </div>
                            <div class="stack-item">
                                <strong>Who this site is for</strong><br>
                                {{ $autopilot['blueprint_name'] ?: 'Blueprint pending' }} · {{ $autopilot['primary_icp_name'] ?: 'ICP pending' }}<br>
                                <span class="muted">{{ $autopilot['problem_solved'] ?: 'Founder problem statement will shape the site message here.' }}</span>
                            </div>
                            <div class="stack-item">
                                <strong>Core promise</strong><br>
                                {{ $autopilotDraft['sell_like_crazy']['core_promise'] ?? 'Core promise pending' }}<br>
                                <span class="muted">{{ $autopilotDraft['sell_like_crazy']['lead_angle'] ?? '' }}</span>
                            </div>
                            <div class="stack-item">
                                <strong>Starter offer</strong><br>
                                {{ $autopilotDraft['starter_offer']['title'] ?? 'Starter offer pending' }} · {{ $autopilotDraft['starter_offer']['price'] ?? '0' }}<br>
                                <span class="muted">{{ $autopilotDraft['starter_offer']['description'] ?? '' }}</span>
                            </div>
                            <div class="stack-item">
                                <strong>Theme audit</strong><br>
                                {{ $autopilotDraft['theme_label'] ?? ('Theme ' . ($autopilotDraft['theme_template'] ?? '1')) }}<br>
                                <span class="muted">
                                    @if (!empty($autopilotDraft['theme_match_reasons'] ?? []))
                                        {{ implode(' · ', $autopilotDraft['theme_match_reasons']) }}
                                    @else
                                        Theme rationale pending.
                                    @endif
                                </span>
                                @if (!empty($autopilotDraft['theme_candidates'] ?? []))
                                    @foreach (($autopilotDraft['theme_candidates'] ?? []) as $candidate)
                                        <div class="muted" style="margin-top:8px;">
                                            {{ $candidate['label'] ?? ('Theme ' . ($candidate['id'] ?? '?')) }} · score {{ $candidate['score'] ?? 0 }}
                                            @if (!empty($candidate['match_reasons'] ?? []))
                                                · {{ implode(', ', $candidate['match_reasons']) }}
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="stack-item">
                                <strong>Image direction</strong><br>
                                <span class="muted">{{ implode(' · ', $autopilotDraft['image_queries'] ?? []) ?: 'Image queries pending' }}</span>
                            </div>
                            <div class="stack-item">
                                <strong>Landing page readiness</strong><br>
                                @php
                                    $qualityAudit = is_array($autopilotDraft['quality_audit'] ?? null) ? $autopilotDraft['quality_audit'] : [];
                                @endphp
                                <div class="muted">Theme fit: {{ ucfirst($qualityAudit['theme_strength'] ?? 'pending') }}</div>
                                <div class="muted">Offers: {{ $qualityAudit['offers_count'] ?? 0 }} · FAQ: {{ $qualityAudit['faq_count'] ?? 0 }} · Proof: {{ $qualityAudit['proof_count'] ?? 0 }} · Pages: {{ $qualityAudit['page_count'] ?? 0 }}</div>
                                <div class="muted">Planned media slots: {{ $qualityAudit['media_slot_count'] ?? 0 }} · CTA ready: {{ !empty($qualityAudit['cta_ready']) ? 'Yes' : 'No' }}</div>
                                <?php if (!empty($qualityAudit['menus'] ?? [])): ?>
                                    <div class="muted">
                                        Booking: {{ !empty($qualityAudit['menus']['online_booking']) ? 'On' : 'Off' }} ·
                                        Service menu: {{ !empty($qualityAudit['menus']['service_menu']) ? 'On' : 'Off' }} ·
                                        Shop menu: {{ !empty($qualityAudit['menus']['shop_menu']) ? 'On' : 'Off' }}
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($autopilotDraft['atlas_handoff']['asset_slots'] ?? [])): ?>
                                <div class="stack-item">
                                    <strong>Creative asset plan</strong><br>
                                    <span class="muted">Hatchers prepared the image brief and asset slots for this website draft.</span>
                                    <?php foreach (($autopilotDraft['atlas_handoff']['asset_slots'] ?? []) as $slot): ?>
                                        <div class="muted" style="margin-top:8px;">
                                            {{ $slot['slot_label'] ?? 'Slot' }} · {{ $slot['query'] ?? '' }} · {{ ucfirst(str_replace('_', ' ', $slot['status'] ?? 'requested')) }}
                                        </div>
                                        <?php if (!empty($slot['preview_url'])): ?>
                                            <div style="margin-top:8px;">
                                                <img src="{{ $slot['preview_url'] }}" alt="{{ $slot['alt_text'] ?? ($slot['slot_label'] ?? 'Website asset') }}" style="width:100%;max-width:220px;border-radius:14px;border:1px solid var(--line);display:block;">
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="stack-item">
                                <strong>Launch checklist</strong><br>
                                <?php foreach (($autopilotDraft['launch_checklist'] ?? []) as $check): ?>
                                    <div class="muted">{{ $check }}</div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!empty($autopilotDraft['funnel_blocks'] ?? [])): ?>
                                <div class="stack-item">
                                    <strong>Direct-response sections</strong><br>
                                    @php
                                        $funnelBlocks = is_array($autopilotDraft['funnel_blocks'] ?? null) ? $autopilotDraft['funnel_blocks'] : [];
                                    @endphp
                                    <?php foreach ($funnelBlocks as $blockKey => $block): ?>
                                        <div class="muted" style="margin-top:8px;">
                                            {{ ucwords(str_replace('_', ' ', (string) $blockKey)) }} ·
                                            {{ is_array($block) ? (($block['title'] ?? $block['body'] ?? ($block[0]['question'] ?? 'Configured'))) : 'Configured' }}
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="stack-item">
                                <strong>Website plan status</strong><br>
                                <span class="muted">
                                    @if ($launchSystem)
                                        {{ ucfirst(str_replace('_', ' ', $launchSystem['status'] ?? 'active')) }} · {{ $launchSystem['applied_at'] ? 'Applied ' . $launchSystem['applied_at'] : 'Not applied yet' }}
                                    @else
                                        This draft has not been locked into the active launch plan yet.
                                    @endif
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="stack">
                            <div class="stack-item">
                                <strong>Ready to draft the first site</strong><br>
                                <span class="muted">Use the founder brief and ICP to generate the first hero, CTA, offer, image direction, and theme suggestion automatically.</span>
                            </div>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('website.generate') }}" style="margin-top: 18px;">
                        @csrf
                        @if($osEmbedMode)
                            <input type="hidden" name="os_embed" value="1">
                        @endif
                        <button class="btn primary" type="submit">{{ $autopilotDraft ? 'Regenerate Website Draft' : 'Generate My First Website' }}</button>
                    </form>
                    @if ($autopilotDraft)
                        <form method="POST" action="{{ route('website.launch-system.apply') }}" style="margin-top: 12px;">
                            @csrf
                            @if($osEmbedMode)
                                <input type="hidden" name="os_embed" value="1">
                            @endif
                            <button class="btn secondary" type="submit">{{ $launchSystem ? 'Refresh Active Launch System' : 'Apply Funnel To Launch System' }}</button>
                        </form>
                        <div class="cta-row" style="margin-top:12px;flex-wrap:wrap;">
                            @foreach (['hero' => 'Regenerate Hero', 'cta' => 'Regenerate CTA', 'offer_stack' => 'Regenerate Offer Stack', 'faq' => 'Regenerate FAQ'] as $blockKey => $label)
                                <form method="POST" action="{{ route('website.draft.regenerate-block') }}" style="margin:0;">
                                    @csrf
                                    @if($osEmbedMode)
                                        <input type="hidden" name="os_embed" value="1">
                                    @endif
                                    <input type="hidden" name="block" value="{{ $blockKey }}">
                                    <button class="btn" type="submit">{{ $label }}</button>
                                </form>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="card">
                    <h2>Draft Preview</h2>
                    <p class="muted" style="margin-bottom: 14px;">This is the current OS-side website plan before the founder fine-tunes the live setup.</p>
                    @if ($autopilotDraft)
                        <div class="stack">
                            @foreach (($autopilotDraft['sections'] ?? []) as $section)
                                <div class="stack-item">
                                    @if (!empty($section['asset']['preview_url']))
                                        <div style="margin-bottom:10px;">
                                            <img src="{{ $section['asset']['preview_url'] }}" alt="{{ $section['asset']['alt_text'] ?? ($section['title'] ?? 'Website section image') }}" style="width:100%;max-width:280px;border-radius:14px;border:1px solid var(--line);display:block;">
                                        </div>
                                    @endif
                                    <strong>{{ $section['title'] ?? 'Section' }}</strong><br>
                                    <span class="muted">{{ $section['body'] ?? '' }}</span>
                                    @foreach (($section['bullets'] ?? []) as $bullet)
                                        <div class="muted">{{ $bullet }}</div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="stack-item">
                            <strong>No draft yet</strong><br>
                            <span class="muted">Once Hatchers generates the first site, the section plan appears here for founder review.</span>
                        </div>
                    @endif
                </div>
            </section>
            @endif

            @if ($websiteStage === 'setup')
            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Finish Website Setup</h2>
                    <p class="muted" style="margin-bottom: 14px;">Set the public name, page path, business type, and theme for the site Hatchers will publish for you.</p>
                    <form method="POST" action="/website/setup" class="stack">
                        @csrf
                        @if($osEmbedMode)
                            <input type="hidden" name="os_embed" value="1">
                        @endif
                        <div class="stack-item">
                            <strong>Storefront type</strong><br>
                            @if (count($engines) === 1)
                                <input type="hidden" name="website_engine" value="{{ $engines[0]['key'] }}" data-engine-select>
                                <div class="pill" style="margin-top:10px;">{{ $engines[0]['label'] }} · {{ $engines[0]['role'] }}</div>
                            @else
                                <select name="website_engine" data-engine-select style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                                    @foreach ($engines as $engine)
                                        <option value="{{ $engine['key'] }}" @selected(old('website_engine', $recommendedEngine) === $engine['key'])>
                                            {{ $engine['label'] }} · {{ $engine['role'] }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="stack-item">
                            <strong>Business type</strong><br>
                            <select name="website_mode" style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                                @foreach (['product' => 'Product business', 'service' => 'Service business', 'hybrid' => 'Hybrid business'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('website_mode', $businessModel) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="stack-item">
                            <strong>Public site title</strong><br>
                            <input
                                type="text"
                                name="website_title"
                                value="{{ $defaultWebsiteTitle }}"
                                style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;"
                                placeholder="Enter the public website title">
                        </div>
                        <div class="stack-item">
                            <strong>Public page path</strong><br>
                            <input
                                type="text"
                                name="website_path"
                                value="{{ old('website_path', $websitePath) }}"
                                style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;"
                                placeholder="your-company-name">
                            <div class="muted" style="margin-top: 10px;">This becomes the public OS path at <strong>app.hatchers.ai/{{ $websitePath ?: 'your-company-name' }}</strong>.</div>
                        </div>
                        <div class="stack-item">
                            <strong>Theme</strong><br>
                            <select name="theme_template" data-theme-select style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                                @foreach ($initialThemeOptions as $theme)
                                    <option value="{{ $theme['id'] }}" @selected($defaultThemeTemplate === $theme['id'])>
                                        {{ $theme['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="muted" style="margin-top: 10px;">Theme choices now follow the selected engine inside this OS setup form.</div>
                        </div>
                        <div class="stack-item">
                            <strong>Theme preview cards</strong><br>
                            <div data-theme-grid style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 12px;"></div>
                        </div>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Save Website Setup</button>
                        </div>
                    </form>
                </div>

            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Add Your First Offer</h2>
                    <p class="muted" style="margin-bottom: 14px;">This lets founders create the first product or service directly from Hatchers AI OS without jumping into engine dashboards first.</p>
                    <form method="POST" action="/website/starter" class="stack">
                        @csrf
                        @if($osEmbedMode)
                            <input type="hidden" name="os_embed" value="1">
                        @endif
                        <div class="stack-item">
                            <strong>Engine</strong><br>
                            @if (count($engines) === 1)
                                <input type="hidden" name="website_engine" value="{{ $engines[0]['key'] }}" data-starter-engine>
                                <div class="pill" style="margin-top:10px;">{{ $engines[0]['label'] }}</div>
                            @else
                                <select name="website_engine" data-starter-engine style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                                    @foreach ($engines as $engine)
                                        <option value="{{ $engine['key'] }}" @selected($recommendedEngine === $engine['key'])>{{ $engine['label'] }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="stack-item">
                            <strong>Starter type</strong><br>
                            <select name="starter_mode" style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                                @if ($supportsProducts)
                                    <option value="product" @selected($businessModel !== 'service')>First product</option>
                                @endif
                                @if ($supportsServices)
                                    <option value="service" @selected($businessModel === 'service')>First service</option>
                                @endif
                            </select>
                        </div>
                        <div class="stack-item">
                            <strong>Title</strong><br>
                            <input type="text" name="starter_title" style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;" placeholder="Starter offer title">
                        </div>
                        <div class="stack-item">
                            <strong>Description</strong><br>
                            <textarea name="starter_description" style="margin-top: 10px; width: 100%; min-height: 110px; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;" placeholder="Describe the first offer"></textarea>
                        </div>
                        <div class="stack-item">
                            <strong>Starting price</strong><br>
                            <input type="number" step="0.01" min="0" name="starter_price" style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;" placeholder="99">
                        </div>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Create First Item</button>
                        </div>
                    </form>
                </div>

            </section>
            @endif

            @if ($websiteStage === 'publish')
            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Publish Your Website</h2>
                    <p class="muted" style="margin-bottom: 14px;">Use this when the draft and setup look right and you want the public OS site to go live.</p>
                    <div class="stack">
                        <div class="stack-item">
                            <strong>Public website</strong><br>
                            {{ preg_replace('#^https?://#', '', $recommendedSubdomain) }}
                        </div>
                        <div class="stack-item">
                            <strong>Current page path</strong><br>
                            app.hatchers.ai/{{ $websitePath }}
                        </div>
                        <div class="stack-item">
                            <strong>Current site status</strong><br>
                            {{ ucfirst(str_replace('_', ' ', $websiteStatus)) }}
                        </div>
                        <div class="stack-item">
                            <strong>What happens on publish</strong><br>
                            Once published, the OS records the site as live under the OS URL structure and routes the storefront behind the scenes.
                        </div>
                    </div>
                    <form method="POST" action="/website/publish" style="margin-top: 18px;">
                        @csrf
                        @if($osEmbedMode)
                            <input type="hidden" name="os_embed" value="1">
                        @endif
                        <input type="hidden" name="website_engine" value="{{ $recommendedEngine }}" data-publish-engine>
                        <button class="btn primary" type="submit">Publish Website</button>
                    </form>
                </div>

                <div class="card">
                    <h2>Before You Publish</h2>
                    <div class="stack" style="margin-top: 14px;">
                        @foreach ($domainModel as $item)
                            <div class="stack-item">
                                <strong>{{ $item['title'] }}</strong><br>
                                {{ $item['value'] }}<br>
                                <span class="muted">{{ $item['description'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @endif

            @if (false && $websiteStage === 'domain')
            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Connect Your Custom Domain</h2>
                    <p class="muted" style="margin-bottom: 14px;">Use this after launch when you want the site to live on your own branded address.</p>
                    <form method="POST" action="/website/domain" class="stack">
                        @csrf
                        @if($osEmbedMode)
                            <input type="hidden" name="os_embed" value="1">
                        @endif
                        <div class="stack-item">
                            <strong>Storefront type</strong><br>
                            @if (count($engines) === 1)
                                <input type="hidden" name="website_engine" value="{{ $engines[0]['key'] }}" data-domain-engine>
                                <div class="pill" style="margin-top:10px;">{{ $engines[0]['label'] }}</div>
                            @else
                                <select name="website_engine" data-domain-engine style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                                    @foreach ($engines as $engine)
                                        <option value="{{ $engine['key'] }}" @selected($recommendedEngine === $engine['key'])>{{ $engine['label'] }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="stack-item">
                            <strong>Custom domain</strong><br>
                            <input type="text" name="custom_domain" value="{{ old('custom_domain', $customDomain ?: $customDomainExample) }}" style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;" placeholder="www.yourbrand.com">
                        </div>
                        <div class="stack-item">
                            <strong>Live preview after DNS</strong><br>
                            {{ preg_replace('#^https?://#', '', 'https://' . ($customDomain ?: $customDomainExample) . '/' . ltrim($websitePath, '/')) }}
                        </div>
                        <div class="stack-item">
                            <strong>DNS target</strong><br>
                            <span data-dns-target>{{ $dnsTargets[$recommendedEngine] ?? '' }}</span><br>
                            <span class="muted">Create a CNAME for `www` pointing to the host above. After DNS propagates, Hatchers can serve the site from your branded domain.</span>
                        </div>
                        <div class="stack-item">
                            <strong>Connection status</strong><br>
                            {{ ucfirst(str_replace('_', ' ', $customDomainStatus)) }}
                        </div>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Save Domain Connection</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h2>Domain Setup</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Start here</strong><br>
                            Add the domain you want to use for your website.
                        </div>
                        <div class="stack-item">
                            <strong>Point the domain once</strong><br>
                            We'll connect it to your website behind the scenes.
                        </div>
                        <div class="stack-item">
                            <strong>Publish first if needed</strong><br>
                            You can publish now and connect the branded domain afterward.
                        </div>
                    </div>
                </div>
            </section>
            @endif

            @if ($websiteStage === 'overview')
            <section class="metrics">
                <div class="card metric">
                    <div class="muted">Founder Workspace</div>
                    <strong>app.hatchers.ai</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Recommended Site</div>
                    <strong style="font-size: 1.15rem;">{{ preg_replace('#^https?://#', '', $recommendedSubdomain) }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Current Site URL</div>
                    <strong style="font-size: 1.15rem;">{{ preg_replace('#^https?://#', '', $currentWebsiteUrl) }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Website Path</div>
                    <strong style="font-size: 1.15rem;">/{{ ltrim($websitePath, '/') }}</strong>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>How Publishing Should Work</h2>
                    <div class="stack" style="margin-top: 14px;">
                        @foreach ($domainModel as $item)
                            <div class="stack-item">
                                <strong>{{ $item['title'] }}</strong><br>
                                {{ $item['value'] }}<br>
                                <span class="muted">{{ $item['description'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card">
                    <h2>Website Setup Notes</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Keep it simple</strong><br>
                            Focus on the website, offer, and contact details first.
                        </div>
                        <div class="stack-item">
                            <strong>Choose one public link</strong><br>
                            Pick the website link you want people to visit.
                        </div>
                        <div class="stack-item">
                            <strong>We'll handle the setup</strong><br>
                            The right website engine will be used automatically in the background.
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                @foreach ($engines as $engine)
                    <div class="card">
                        <h2>{{ $engine['label'] }}</h2>
                        <div class="stack" style="margin-top: 14px;">
                            <div class="stack-item">
                                <strong>Role</strong><br>
                                {{ $engine['role'] }}
                            </div>
                            <div class="stack-item">
                                <strong>Website title</strong><br>
                                {{ $engine['website_title'] }}
                            </div>
                            <div class="stack-item">
                                <strong>Readiness</strong><br>
                                {{ $engine['readiness_score'] }}%
                            </div>
                            <div class="stack-item">
                                <strong>Current summary</strong><br>
                                {{ $engine['summary'] }}
                            </div>
                            <div class="stack-item">
                                <strong>Theme</strong><br>
                                {{ $engine['theme'] }}
                            </div>
                            @php
                                $engineThemes = $themeOptions[$engine['key']] ?? [];
                            @endphp
                            @if (!empty($engineThemes))
                                <div class="stack-item">
                                    <strong>Available themes</strong><br>
                                    {{ implode(' · ', array_map(fn ($theme) => $theme['label'], array_slice($engineThemes, 0, 8))) }}
                                </div>
                            @endif
                            <div class="stack-item">
                                <strong>Public website</strong><br>
                                {{ preg_replace('#^https?://#', '', $engine['website_url']) }}
                            </div>
                        </div>
                        <div class="cta-row">
                            <a class="btn primary" href="{{ route('founder.commerce') }}">Open Commerce</a>
                        </div>
                        @if (!empty($engine['updated_at']))
                            <p class="muted" style="margin-top: 14px;">Updated {{ $engine['updated_at'] }}</p>
                        @endif
                    </div>
                @endforeach
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>What's Ready So Far</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Business details</strong><br>
                            Your business information is already available to help shape the website draft.
                        </div>
                        <div class="stack-item">
                            <strong>Website settings</strong><br>
                            You can manage the website title, link, and publish flow from here.
                        </div>
                        <div class="stack-item">
                            <strong>Draft updates</strong><br>
                            Changes you confirm here can be applied directly to the live website setup.
                        </div>
                        <div class="stack-item">
                            <strong>Current setup</strong><br>
                            Your current business model and website type are already taken into account.
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>What To Do Next</h2>
                    <div class="stack" style="margin-top: 14px;">
                        @foreach ($nextSteps as $step)
                            <div class="stack-item">
                                <strong>{{ $step['title'] }}</strong><br>
                                {{ $step['description'] }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @endif
            </div>
        </main>

        @unless($osEmbedMode)
        <aside class="workspace-rightbar">
            <div class="workspace-rightbar-inner">
                <h3>Launch Flow</h3>
                <div class="workspace-rail-list">
                    <div class="workspace-rail-item">
                        <strong>Status</strong><br>
                        <span class="muted">{{ ucfirst(str_replace('_', ' ', $websiteStatus)) }} · {{ ucfirst(str_replace('_', ' ', $generationStatus)) }}</span>
                    </div>
                    <div class="workspace-rail-item">
                        <strong>Current step</strong><br>
                        <span class="muted">{{ ucfirst($websiteStage) }}</span>
                    </div>
                    <div class="workspace-rail-item">
                        <strong>Public path</strong><br>
                        <span class="muted">{{ preg_replace('#^https?://#', '', $currentWebsiteUrl) }}</span>
                    </div>
                    <div class="workspace-rail-item">
                        <strong>Website type</strong><br>
                        <span class="muted">{{ ucfirst($businessModel) }} business</span>
                    </div>
                </div>

                <h3 style="margin-top:22px;">Next Steps</h3>
                <div class="workspace-rail-list">
                    @foreach (array_slice($nextSteps, 0, 4) as $step)
                        <div class="workspace-rail-item">
                            <strong>{{ $step['title'] }}</strong><br>
                            <span class="muted">{{ $step['description'] }}</span>
                        </div>
                    @endforeach
                </div>

                <h3 style="margin-top:22px;">Website Path</h3>
                <div class="workspace-rail-list">
                    @foreach ($domainModel as $item)
                        <div class="workspace-rail-item">
                            <strong>{{ $item['title'] }}</strong><br>
                            <span class="muted">{{ $item['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
        @endunless
    </div>

    <script>
        (() => {
            const themeMap = @json($themeOptions);
            const dnsTargets = @json($dnsTargets);
            const engineSelect = document.querySelector('[data-engine-select]');
            const modeSelect = document.querySelector('select[name="website_mode"]');
            const themeSelect = document.querySelector('[data-theme-select]');
            const themeGrid = document.querySelector('[data-theme-grid]');
            const publishEngine = document.querySelector('[data-publish-engine]');
            const starterEngine = document.querySelector('[data-starter-engine]');
            const domainEngine = document.querySelector('[data-domain-engine]');
            const dnsTarget = document.querySelector('[data-dns-target]');
            let selectedTheme = @json(old('theme_template', $recommendedCard['theme']));

            if (!engineSelect || !themeSelect) {
                return;
            }

            const syncEngineFromMode = () => {
                if (!modeSelect) {
                    return;
                }

                if (modeSelect.value === 'product') {
                    engineSelect.value = 'bazaar';
                } else if (modeSelect.value === 'service') {
                    engineSelect.value = 'servio';
                }
            };

            const populateThemes = (engine) => {
                const themes = Array.isArray(themeMap[engine]) ? themeMap[engine] : [];
                themeSelect.innerHTML = '';
                if (themeGrid) {
                    themeGrid.innerHTML = '';
                }

                themes.forEach((theme, index) => {
                    const option = document.createElement('option');
                    option.value = theme.id;
                    option.textContent = theme.label;
                    if ((selectedTheme && selectedTheme === theme.id) || (!selectedTheme && index === 0)) {
                        option.selected = true;
                    }
                    themeSelect.appendChild(option);

                    if (themeGrid) {
                        const card = document.createElement('button');
                        card.type = 'button';
                        card.style.border = '1px solid var(--line)';
                        card.style.borderRadius = '16px';
                        card.style.background = '#fff';
                        card.style.padding = '10px';
                        card.style.cursor = 'pointer';
                        card.style.textAlign = 'left';
                        card.dataset.themeId = theme.id;

                        const image = document.createElement('div');
                        image.style.height = '110px';
                        image.style.borderRadius = '12px';
                        image.style.background = theme.preview_url
                            ? `center / cover no-repeat url("${theme.preview_url}")`
                            : 'linear-gradient(135deg, #f0e7da, #fffdf8)';
                        card.appendChild(image);

                        const label = document.createElement('div');
                        label.style.marginTop = '8px';
                        label.style.fontWeight = '600';
                        label.textContent = theme.label;
                        card.appendChild(label);

                        if (option.selected) {
                            card.style.boxShadow = '0 0 0 2px var(--ink) inset';
                        }

                        card.addEventListener('click', () => {
                            selectedTheme = theme.id;
                            themeSelect.value = theme.id;
                            [...themeGrid.children].forEach((child) => {
                                child.style.boxShadow = 'none';
                            });
                            card.style.boxShadow = '0 0 0 2px var(--ink) inset';
                        });

                        themeGrid.appendChild(card);
                    }
                });

                if (publishEngine) {
                    publishEngine.value = engine;
                }
                if (starterEngine) {
                    starterEngine.value = engine;
                }
                if (domainEngine) {
                    domainEngine.value = engine;
                }
                if (dnsTarget) {
                    dnsTarget.textContent = dnsTargets[engine] || '';
                }
            };

            if (modeSelect) {
                modeSelect.addEventListener('change', () => {
                    syncEngineFromMode();
                    populateThemes(engineSelect.value);
                });
            }

            engineSelect.addEventListener('change', () => {
                selectedTheme = '';
                populateThemes(engineSelect.value);
            });
            if (domainEngine) {
                domainEngine.addEventListener('change', () => {
                    if (dnsTarget) {
                        dnsTarget.textContent = dnsTargets[domainEngine.value] || '';
                    }
                });
            }
            syncEngineFromMode();
            populateThemes(engineSelect.value);
        })();
    </script>
@endsection
