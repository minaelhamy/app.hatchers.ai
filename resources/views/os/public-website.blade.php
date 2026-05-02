@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'public-website-page')

@section('head')
    <style>
        .page.public-website-page { padding: 0; background: #f4f0e7; }
        .site-shell {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(210, 178, 140, 0.22), transparent 30%),
                radial-gradient(circle at right center, rgba(149, 190, 176, 0.16), transparent 24%),
                linear-gradient(180deg, #faf7f1 0%, #f4f0e7 100%);
            color: #2d2925;
        }
        .engine-shell { min-height: 100vh; background: #f7f3eb; display: grid; grid-template-rows: auto 1fr; }
        .engine-bar {
            padding: 14px 20px;
            border-bottom: 1px solid rgba(220, 207, 191, 0.65);
            background: rgba(255, 252, 247, 0.96);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }
        .engine-bar-copy { color: #625848; font-size: 0.96rem; }
        .engine-frame-wrap { min-height: calc(100vh - 70px); }
        .engine-frame { width: 100%; min-height: calc(100vh - 70px); border: 0; display: block; background: #fff; }
        .site-wrap { width: min(1180px, calc(100vw - 40px)); margin: 0 auto; }
        .site-notice {
            padding: 18px 20px;
            border-radius: 24px;
            border: 1px solid rgba(173, 154, 134, 0.22);
            margin-bottom: 18px;
            background: rgba(255, 255, 255, 0.84);
            box-shadow: 0 18px 36px rgba(68, 52, 36, 0.06);
        }
        .site-hero { padding: 34px 24px 56px; }
        .site-brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }
        .site-brand img {
            width: 150px;
            border-radius: 22px;
            border: 1px solid rgba(173, 154, 134, 0.18);
            background: rgba(255, 255, 255, 0.72);
            padding: 14px;
        }
        .site-brand-fallback {
            width: 150px;
            height: 118px;
            display: grid;
            align-content: center;
            gap: 6px;
            padding: 18px;
            border-radius: 22px;
            border: 1px solid rgba(173, 154, 134, 0.18);
            background: rgba(255, 255, 255, 0.72);
            box-sizing: border-box;
        }
        .site-brand-fallback strong {
            font-size: 1.25rem;
            line-height: 1;
            letter-spacing: -0.04em;
        }
        .site-brand-fallback span {
            color: #7f6f62;
            font-size: 0.82rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .site-host { color: #7f6f62; font-size: 0.96rem; }
        .site-hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.02fr) minmax(360px, 0.98fr);
            gap: 28px;
            align-items: stretch;
        }
        .site-badge {
            display: inline-flex;
            align-items: center;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(173, 154, 134, 0.22);
            color: #876c56;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            backdrop-filter: blur(12px);
        }
        .site-eyebrow {
            margin: 18px 0 12px;
            color: #8c735d;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }
        .site-hero h1 {
            margin: 0 0 18px;
            font-size: clamp(2.9rem, 7vw, 6.1rem);
            line-height: 0.92;
            letter-spacing: -0.06em;
            max-width: 10ch;
        }
        .site-lede,
        .site-meta {
            color: #5f564b;
            line-height: 1.75;
        }
        .site-lede {
            font-size: 1.12rem;
            max-width: 58ch;
            margin: 0 0 16px;
        }
        .site-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }
        .site-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            padding: 0 20px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 700;
            transition: transform 180ms ease, box-shadow 180ms ease;
        }
        .site-cta:hover { transform: translateY(-1px); }
        .site-cta.primary {
            background: #1f1d1b;
            color: #fff;
            box-shadow: 0 18px 30px rgba(31, 29, 27, 0.18);
        }
        .site-cta.secondary {
            background: rgba(255, 255, 255, 0.88);
            color: #2d2925;
            border: 1px solid rgba(173, 154, 134, 0.22);
        }
        .site-card {
            background: rgba(255, 255, 255, 0.84);
            border: 1px solid rgba(173, 154, 134, 0.18);
            border-radius: 28px;
            box-shadow: 0 22px 60px rgba(68, 52, 36, 0.08);
            backdrop-filter: blur(12px);
        }
        .site-showcase {
            position: relative;
            overflow: hidden;
            min-height: 600px;
            display: grid;
            align-content: end;
            padding: 28px;
            border-radius: 34px;
            background:
                linear-gradient(180deg, rgba(31, 28, 25, 0.14), rgba(31, 28, 25, 0.68)),
                var(--hero-image, linear-gradient(135deg, #b89173, #90b7a5));
            background-size: cover;
            background-position: center;
            color: #fff;
        }
        .site-showcase-note {
            display: inline-flex;
            width: fit-content;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.18);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .site-showcase-content {
            display: grid;
            gap: 14px;
            position: relative;
            z-index: 1;
        }
        .site-showcase-title {
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -0.05em;
            max-width: 12ch;
        }
        .site-showcase-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 8px;
        }
        .site-stat {
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.14);
        }
        .site-stat strong {
            display: block;
            font-size: 1.35rem;
            margin-bottom: 4px;
        }
        .site-sections { padding: 0 24px 60px; }
        .site-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 22px;
            margin-top: -34px;
        }
        .site-stack { display: grid; gap: 22px; }
        .site-section { padding: 28px; }
        .site-section h2 {
            margin: 0 0 10px;
            font-size: clamp(1.7rem, 3vw, 2.4rem);
            letter-spacing: -0.04em;
        }
        .site-feature-grid,
        .site-offer-grid,
        .site-proof-grid {
            display: grid;
            gap: 16px;
            margin-top: 18px;
        }
        .site-feature-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .site-offer-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .site-mini-card,
        .site-offer-card,
        .site-side-card {
            padding: 22px;
            border-radius: 24px;
            background: #fff;
            border: 1px solid rgba(173, 154, 134, 0.14);
        }
        .site-mini-card h3,
        .site-offer-card h3 {
            margin: 0 0 10px;
            font-size: 1.18rem;
            letter-spacing: -0.03em;
        }
        .site-bullet-list {
            list-style: none;
            margin: 16px 0 0;
            padding: 0;
            display: grid;
            gap: 10px;
        }
        .site-bullet-list li {
            position: relative;
            padding-left: 18px;
            color: #5f564b;
            line-height: 1.6;
        }
        .site-bullet-list li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0.72em;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #c18b67;
            transform: translateY(-50%);
        }
        .site-offer-type {
            display: inline-flex;
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(193, 139, 103, 0.12);
            color: #8f6041;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .site-price {
            margin-top: 16px;
            font-size: 1.4rem;
            font-weight: 800;
            color: #1f1d1b;
        }
        .site-status {
            display: inline-flex;
            margin-top: 12px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(144, 183, 165, 0.16);
            color: #507264;
            font-size: 0.84rem;
            font-weight: 700;
        }
        .site-quote {
            color: #383129;
            line-height: 1.7;
            font-size: 1.1rem;
        }
        .site-side-card + .site-side-card { margin-top: 16px; }
        .site-footer { padding: 0 24px 60px; }
        .site-footer-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 20px;
            align-items: center;
            padding: 24px 28px;
        }
        .site-contact-list {
            display: grid;
            gap: 10px;
            margin-top: 16px;
            color: #5f564b;
        }
        @media (max-width: 1024px) {
            .site-hero-grid,
            .site-main-grid,
            .site-footer-card,
            .site-feature-grid,
            .site-offer-grid {
                grid-template-columns: 1fr;
            }
            .site-showcase { min-height: 420px; }
        }
        @media (max-width: 640px) {
            .site-brand {
                align-items: flex-start;
                flex-direction: column;
            }
            .site-hero,
            .site-sections,
            .site-footer { padding-inline: 18px; }
            .site-wrap { width: min(100vw - 24px, 1180px); }
            .site-showcase-grid { grid-template-columns: 1fr; }
            .site-section,
            .site-footer-card { padding: 22px; }
        }
    </style>
@endsection

@section('content')
    @php
        $siteTitle = $site['title'];
        $engineStorefrontUrl = $site['source_storefront_url'] ?? '';
        $usesEngineStorefront = (bool) ($site['uses_engine_storefront'] ?? false);
        $hero = $site['hero'];
        $metrics = $site['metrics'];
        $offers = $site['offers'];
        $proof = $site['proof'];
        $operations = $site['operations'];
        $contact = $site['contact'];
        $generatedSections = $site['generated_sections'] ?? [];
        $imageQueries = $site['image_queries'] ?? [];
        $heroImageUrl = $site['hero_image_url'] ?? null;
        $realOffers = collect($offers)->where('type', '!=', 'placeholder')->values();
        $featureSections = collect($generatedSections)->take(3)->values();
        $storySection = collect($generatedSections)->first(function ($section) {
            $haystack = strtolower(trim(($section['title'] ?? '') . ' ' . ($section['body'] ?? '')));

            return str_contains($haystack, 'about') || str_contains($haystack, 'story') || str_contains($haystack, 'founder');
        }) ?: ($generatedSections[0] ?? null);
        $leadBullets = collect($generatedSections)
            ->flatMap(fn ($section) => (array) ($section['bullets'] ?? []))
            ->filter()
            ->take(6)
            ->values();
    @endphp

    @if ($usesEngineStorefront && $engineStorefrontUrl !== '')
        <div class="engine-shell">
            <div class="engine-bar">
                <div>
                    <strong>{{ $siteTitle }}</strong>
                    <div class="engine-bar-copy">This website is using the real {{ strtoupper($site['engine']) }} storefront template and features, published through Hatchers OS.</div>
                </div>
                <div class="engine-bar-copy">{{ preg_replace('#^https?://#', '', $engineStorefrontUrl) }}</div>
            </div>
            <div class="engine-frame-wrap">
                <iframe
                    class="engine-frame"
                    src="{{ $engineStorefrontUrl }}"
                    title="{{ $siteTitle }}"
                    loading="eager"
                    referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
            </div>
        </div>
    @else
        <div class="site-shell">
            <section class="site-hero">
                <div class="site-wrap">
                    @if (session('success'))
                        <div class="site-notice" style="border-color:rgba(44,122,87,0.22);background:rgba(44,122,87,0.08);">
                            <strong style="color:#2c7a57;">Request received</strong>
                            <div class="site-meta" style="margin-top:8px;">{{ session('success') }}</div>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="site-notice" style="border-color:rgba(179,34,83,0.22);background:rgba(179,34,83,0.08);">
                            <strong style="color:#bf245e;">Something needs attention</strong>
                            <div class="site-meta" style="margin-top:8px;">{{ session('error') }}</div>
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="site-notice" style="border-color:rgba(154,107,27,0.22);background:rgba(154,107,27,0.08);">
                            <strong style="color:#9a6b1b;">A few fields still need attention</strong>
                            <div class="site-meta" style="margin-top:8px;">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="site-brand">
                        @if (!empty($site['logo_url']))
                            <img src="{{ $site['logo_url'] }}" alt="{{ $siteTitle }}">
                        @else
                            <div class="site-brand-fallback" aria-label="{{ $siteTitle }}">
                                <strong>{{ str($siteTitle)->limit(18, '') }}</strong>
                                <span>Live brand</span>
                            </div>
                        @endif
                        <div class="site-host">Published on {{ request()->getHost() }}</div>
                    </div>

                    <div class="site-hero-grid">
                        <div>
                            <div class="site-badge">{{ ucfirst($site['business_model']) }} business</div>
                            <div class="site-eyebrow">{{ $hero['eyebrow'] }}</div>
                            <h1>{{ $hero['headline'] }}</h1>
                            <p class="site-lede">{{ $hero['subhead'] }}</p>
                            <p class="site-meta" style="max-width:58ch;">{{ $hero['brief'] }}</p>
                            @if ($leadBullets->isNotEmpty())
                                <ul class="site-bullet-list" style="max-width:56ch;">
                                    @foreach ($leadBullets->take(3) as $bullet)
                                        <li>{{ $bullet }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            <div class="site-actions">
                                @if ($realOffers->isNotEmpty())
                                    <a href="#offers" class="site-cta primary">{{ $hero['primary_cta'] }}</a>
                                @endif
                                <a href="#contact" class="site-cta secondary">{{ $hero['secondary_cta'] }}</a>
                            </div>
                        </div>

                        <div class="site-showcase" @if ($heroImageUrl) style="--hero-image:url('{{ $heroImageUrl }}');" @endif>
                            <div class="site-showcase-content">
                                <span class="site-showcase-note">Live website</span>
                                <div class="site-showcase-title">{{ $siteTitle }}</div>
                                <div style="color:rgba(255,255,255,0.84);line-height:1.7;max-width:34ch;">
                                    This page now feels like a real brand surface instead of an internal operating snapshot, while the deeper storefront sync continues behind the scenes.
                                </div>
                                @if (!empty($metrics))
                                    <div class="site-showcase-grid">
                                        @foreach ($metrics as $metric)
                                            <div class="site-stat">
                                                <strong>{{ $metric['value'] }}</strong>
                                                <span>{{ $metric['label'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="site-sections">
                <div class="site-wrap site-main-grid">
                    <div class="site-stack">
                        @if ($featureSections->isNotEmpty())
                            <section class="site-card site-section">
                                <div class="site-eyebrow">Why this brand stands out</div>
                                <h2>A calmer, more intentional website built from the founder brief.</h2>
                                <p class="site-meta">The goal here is not to dump operations onto the page. It is to turn the founder’s positioning into a homepage that feels clear, premium, and immediately useful to the right visitor.</p>
                                <div class="site-feature-grid">
                                    @foreach ($featureSections as $section)
                                        <article class="site-mini-card">
                                            <h3>{{ $section['title'] ?: 'Why people care' }}</h3>
                                            <p class="site-meta">{{ $section['body'] }}</p>
                                            @if (!empty($section['bullets']))
                                                <ul class="site-bullet-list">
                                                    @foreach (array_slice($section['bullets'], 0, 3) as $bullet)
                                                        <li>{{ $bullet }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        <section id="offers" class="site-card site-section">
                            <div class="site-eyebrow">Featured offers</div>
                            <h2>Lead with the strongest next step, not a generic placeholder.</h2>
                            <p class="site-meta">These offers should feel like the first polished version of the founder’s commercial stack: what someone can buy, how it helps, and why it is worth saying yes now.</p>
                            <div class="site-offer-grid">
                                @foreach ($offers as $offer)
                                    <article class="site-offer-card">
                                        <span class="site-offer-type">{{ $offer['type'] === 'placeholder' ? 'coming soon' : $offer['type'] }}</span>
                                        <h3 style="margin-top:14px;">{{ $offer['title'] }}</h3>
                                        <p class="site-meta">{{ $offer['meta'] }}</p>
                                        @if (!empty($offer['details']))
                                            <ul class="site-bullet-list">
                                                @foreach (array_slice($offer['details'], 0, 3) as $detail)
                                                    <li>{{ $detail }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @if ($offer['price'] !== '')
                                            <div class="site-price">{{ $offer['price'] }}</div>
                                        @endif
                                        <div class="site-status">{{ $offer['status'] }}</div>
                                    </article>
                                @endforeach
                            </div>
                        </section>

                        @if ($storySection)
                            <section class="site-card site-section">
                                <div class="site-eyebrow">Founder story</div>
                                <h2>{{ $storySection['title'] ?: 'What makes this business feel trustworthy' }}</h2>
                                <p class="site-meta">{{ $storySection['body'] }}</p>
                                @if (!empty($storySection['bullets']))
                                    <ul class="site-bullet-list">
                                        @foreach ($storySection['bullets'] as $bullet)
                                            <li>{{ $bullet }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </section>
                        @endif

                        @if (!empty($operations))
                            <section class="site-card site-section">
                                <div class="site-eyebrow">How it works</div>
                                <h2>Important details are visible without making the page feel mechanical.</h2>
                                <div class="site-feature-grid" style="grid-template-columns:repeat(2, minmax(0, 1fr));">
                                    @foreach ($operations as $operation)
                                        <article class="site-mini-card">
                                            <h3>{{ $operation['title'] }}</h3>
                                            <div class="site-meta">
                                                @foreach ($operation['items'] as $item)
                                                    <div style="margin-bottom:12px;">
                                                        <strong style="display:block;color:#2d2925;">{{ $item['title'] }}</strong>
                                                        <div>{{ $item['meta'] }}</div>
                                                        @if (!empty($item['detail']))
                                                            <div>{{ $item['detail'] }}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>

                    <aside>
                        <div class="site-card site-side-card">
                            <div class="site-eyebrow" style="margin-top:0;">Live website</div>
                            <h2 style="font-size:1.65rem;margin-bottom:8px;">{{ $siteTitle }}</h2>
                            <p class="site-meta">Published at {{ request()->getHost() }}/{{ ltrim($site['path'], '/') }} and now presented as a polished local website rather than a dull OS summary.</p>
                            @if (!empty($site['updated_at']))
                                <div class="site-meta" style="margin-top:14px;">Last synced {{ $site['updated_at'] }}</div>
                            @endif
                            <div class="site-status">{{ ucfirst($site['business_model']) }} business</div>
                        </div>

                        @if (!empty($proof))
                            <div class="site-card site-side-card">
                                <div class="site-eyebrow" style="margin-top:0;">Trust signals</div>
                                <div class="site-proof-grid">
                                    @foreach ($proof as $item)
                                        <article class="site-mini-card" style="padding:18px;">
                                            <h3>{{ $item['title'] }}</h3>
                                            <p class="site-meta">{{ $item['description'] }}</p>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (!empty($imageQueries))
                            <div class="site-card site-side-card">
                                <div class="site-eyebrow" style="margin-top:0;">Visual direction</div>
                                <p class="site-quote">Prepared around a calmer, more intentional visual mood so the founder starts from something that already feels like a brand.</p>
                                <ul class="site-bullet-list">
                                    @foreach (array_slice($imageQueries, 0, 5) as $query)
                                        <li>{{ $query }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </aside>
                </div>
            </div>

            <footer id="contact" class="site-footer">
                <div class="site-wrap site-card site-footer-card">
                    <div>
                        <div class="site-eyebrow" style="margin-top:0;">Contact</div>
                        <div style="font-size:1.75rem;font-weight:800;letter-spacing:-0.04em;">Ready to talk to {{ $contact['company'] }}?</div>
                        <div class="site-contact-list">
                            @if ($contact['founder_name'] !== '')
                                <div>Founder: {{ $contact['founder_name'] }}</div>
                            @endif
                            @if ($contact['email'] !== '')
                                <div>Email: <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></div>
                            @endif
                        </div>
                    </div>
                    @if ($contact['email'] !== '')
                        <a href="mailto:{{ $contact['email'] }}" class="site-cta primary">Email the founder</a>
                    @endif
                </div>
            </footer>
        </div>
    @endif
@endsection
