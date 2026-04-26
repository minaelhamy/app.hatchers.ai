@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'promo-kit-page')

@section('head')
    <style>
        .page.promo-kit-page { padding: 0; background: #f8f5ee; }
        .kit-shell { min-height: 100vh; padding: 28px 20px 40px; }
        .kit-wrap { width: min(980px, calc(100vw - 32px)); margin: 0 auto; }
        .kit-header, .kit-card { background: rgba(255,255,255,0.94); border: 1px solid rgba(220,207,191,0.72); border-radius: 22px; box-shadow: 0 12px 32px rgba(60,45,28,0.05); }
        .kit-header { padding: 24px; margin-bottom: 14px; }
        .kit-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .kit-card { padding: 20px; }
        .kit-eyebrow { font-size: 0.82rem; letter-spacing: 0.12em; color: #7d6b56; margin-bottom: 10px; }
        .kit-title { font-size: clamp(1.9rem, 3vw, 3rem); letter-spacing: -0.03em; margin: 0 0 10px; }
        .kit-muted { color: #625848; line-height: 1.65; }
        .kit-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }
        .kit-btn { display: inline-flex; align-items: center; justify-content: center; padding: 11px 16px; border-radius: 999px; text-decoration: none; font-weight: 600; border: 1px solid rgba(220,207,191,0.8); background: #fff; color: #181717; cursor: pointer; }
        .kit-btn.primary { background: #181717; color: #fff; border-color: #181717; }
        .kit-copy { white-space: pre-line; color: #2f2a22; line-height: 1.7; }
        .kit-list { margin: 0; padding-left: 18px; color: #625848; line-height: 1.7; }
        .kit-url { margin-top: 10px; word-break: break-all; font-size: 0.95rem; color: #51483b; }
        .asset-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 14px; }
        .asset-board { border-radius: 28px; padding: 22px; min-height: 360px; display: grid; gap: 14px; position: relative; overflow: hidden; }
        .asset-board::after { content: ""; position: absolute; inset: auto -40px -40px auto; width: 180px; height: 180px; border-radius: 999px; background: rgba(255,255,255,0.22); }
        .asset-label { font-size: 0.78rem; letter-spacing: 0.12em; text-transform: uppercase; opacity: 0.78; }
        .asset-headline { font-size: clamp(1.6rem, 3vw, 2.4rem); line-height: 0.95; letter-spacing: -0.04em; max-width: 12ch; }
        .asset-subheadline { font-size: 1rem; line-height: 1.55; max-width: 32ch; }
        .asset-pill-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .asset-pill { display: inline-flex; align-items: center; justify-content: center; padding: 7px 10px; border-radius: 999px; background: rgba(255,255,255,0.76); color: inherit; font-size: 0.82rem; }
        .asset-url-box { margin-top: auto; padding: 12px 14px; border-radius: 18px; background: rgba(255,255,255,0.82); color: inherit; }
        .asset-url-box strong { display: block; margin-bottom: 6px; }
        .asset-url-line { font-size: 0.9rem; word-break: break-all; line-height: 1.5; }
        .asset-qr-box { width: 124px; height: 124px; border-radius: 18px; background:
            linear-gradient(90deg, rgba(255,255,255,0.9) 10px, transparent 10px) 0 0/22px 22px,
            linear-gradient(rgba(255,255,255,0.9) 10px, transparent 10px) 0 0/22px 22px,
            rgba(255,255,255,0.28);
            border: 2px solid rgba(255,255,255,0.75);
            display: grid; place-items: center; font-size: 0.74rem; text-align: center; padding: 12px; }
        .asset-card-mini { border-radius: 22px; padding: 18px; background: #fff; border: 1px solid rgba(220,207,191,0.72); display: grid; gap: 10px; }
        .asset-card-mini strong { font-size: 1.05rem; }
        .asset-variant-list { display: grid; gap: 10px; }
        @media print {
            .kit-actions { display: none; }
            .kit-shell { padding: 0; }
            .kit-header, .kit-card { box-shadow: none; }
        }
        @media (max-width: 860px) {
            .kit-grid { grid-template-columns: 1fr; }
            .asset-grid { grid-template-columns: 1fr; }
        }
    </style>
@endsection

@section('content')
    @php
        $tone = $kit['brand_tone'] ?? ['accent' => '#1f6f78', 'accent_soft' => '#ebfbfd', 'ink' => '#173437'];
    @endphp
    <div class="kit-shell">
        <div class="kit-wrap">
            <section class="kit-header">
                <div class="kit-eyebrow">OFFLINE CAMPAIGN KIT</div>
                <h1 class="kit-title">{{ $kit['title'] }}</h1>
                <div class="kit-muted">{{ $kit['vertical_label'] }} · {{ $kit['source_channel_label'] }} · Promo {{ $kit['promo_code'] }}</div>
                <div class="kit-muted" style="margin-top:10px;">Use this as a ready-to-deploy founder asset pack. Everything here is aligned to the same offer, promo code, and tracked URL already stored inside Hatchers OS.</div>
                <div class="kit-muted" style="margin-top:10px;">{{ $kit['stats']['captured_leads'] ?? 0 }} leads captured · {{ $kit['stats']['won_leads'] ?? 0 }} won · {{ $kit['stats']['follow_up_due'] ?? 0 }} follow-up due</div>
                <div class="kit-actions">
                    <button class="kit-btn primary" type="button" onclick="window.print()">Print kit</button>
                    <a class="kit-btn" href="{{ route('founder.first-100.promo-links.qr-svg', $promoLink) }}">Download QR SVG</a>
                    <a class="kit-btn" href="{{ route('founder.first-100.promo-links.qr-png', $promoLink) }}">Download QR PNG</a>
                    <a class="kit-btn" href="{{ route('founder.first-100') }}">Back to First 100</a>
                </div>
            </section>

            <section class="kit-card" style="margin-bottom:14px;">
                <div class="kit-eyebrow">VISUAL ASSET BOARDS</div>
                <div class="kit-muted">These are ready-made founder layouts you can print, screenshot, or hand to a designer later. They stay aligned to the tracked promo link, CTA, and offer you already saved in Hatchers OS.</div>
                <div class="asset-grid">
                    <article class="asset-board" style="background: linear-gradient(180deg, {{ $tone['accent_soft'] }}, #ffffff); color: {{ $tone['ink'] }};">
                        <div class="asset-label">QR-Ready Flyer</div>
                        <div class="asset-pill-row">
                            <span class="asset-pill">{{ $kit['source_channel_label'] }}</span>
                            <span class="asset-pill">Promo {{ $kit['promo_code'] }}</span>
                        </div>
                        <div class="asset-headline">{{ $kit['flyer']['headline'] }}</div>
                        <div class="asset-subheadline">{{ $kit['flyer']['subheadline'] }}</div>
                        <div class="asset-pill-row">
                            @foreach (array_slice($kit['proof_points'], 0, 3) as $point)
                                <span class="asset-pill">{{ $point }}</span>
                            @endforeach
                        </div>
                        <div style="display:flex;align-items:end;justify-content:space-between;gap:14px;">
                            <div class="asset-url-box">
                                <strong>{{ $kit['cta_label'] }}</strong>
                                <div class="asset-url-line">{{ $kit['promo_url'] }}</div>
                            </div>
                            <div class="asset-qr-box" style="background:#fff;border:none;padding:8px;">
                                <div style="width:100%;height:100%;display:grid;place-items:center;">
                                    {!! $qrSvg !!}
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="asset-board" style="background: linear-gradient(180deg, #fff, {{ $tone['accent_soft'] }}); color: {{ $tone['ink'] }};">
                        <div class="asset-label">Referral Card</div>
                        <div class="asset-headline" style="max-width: 14ch;">{{ $kit['offer_title'] }}</div>
                        <div class="asset-subheadline">{{ $kit['referral_card']['front'] }}</div>
                        <div class="asset-url-box">
                            <strong>Give this code: {{ $kit['promo_code'] }}</strong>
                            <div class="asset-url-line">{{ $kit['referral_card']['back'] }}</div>
                        </div>
                    </article>

                    <article class="asset-board" style="background: {{ $tone['accent'] }}; color: #fff;">
                        <div class="asset-label">Story / Share Card</div>
                        <div class="asset-headline" style="max-width: 13ch;">{{ $kit['flyer']['headline'] }}</div>
                        <div class="asset-subheadline">{{ $kit['flyer']['body'] }}</div>
                        <div class="asset-pill-row">
                            <span class="asset-pill" style="background: rgba(255,255,255,0.18); color:#fff;">{{ $kit['cta_label'] }}</span>
                            <span class="asset-pill" style="background: rgba(255,255,255,0.18); color:#fff;">Promo {{ $kit['promo_code'] }}</span>
                        </div>
                        <div class="asset-url-box" style="background: rgba(255,255,255,0.16); color:#fff;">
                            <strong>Tracked link</strong>
                            <div class="asset-url-line">{{ $kit['promo_url'] }}</div>
                        </div>
                    </article>

                    <article class="asset-board" style="background: #fff; color: {{ $tone['ink'] }}; border: 2px dashed {{ $tone['accent'] }};">
                        <div class="asset-label">Business Card CTA</div>
                        <div class="asset-pill-row">
                            <span class="asset-pill" style="background: {{ $tone['accent_soft'] }};">{{ $kit['vertical_label'] }}</span>
                        </div>
                        <div class="asset-subheadline">{{ $kit['street_pitch']['opening'] }}</div>
                        <div class="asset-url-box" style="background: {{ $tone['accent_soft'] }};">
                            <strong>{{ $kit['cta_label'] }}</strong>
                            <div class="asset-url-line">{{ $kit['promo_url'] }}</div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="kit-grid">
                <article class="kit-card">
                    <div class="kit-eyebrow">FLYER</div>
                    <strong>{{ $kit['flyer']['headline'] }}</strong>
                    <div class="kit-copy" style="margin-top:10px;">{{ $kit['flyer']['subheadline'] }}</div>
                    <div class="kit-copy" style="margin-top:10px;">{{ $kit['flyer']['body'] }}</div>
                    <div class="kit-copy" style="margin-top:10px;"><strong>CTA:</strong> {{ $kit['flyer']['cta'] }}</div>
                    <div class="kit-url"><strong>Tracked URL:</strong> {{ $kit['promo_url'] }}</div>
                </article>

                <article class="kit-card">
                    <div class="kit-eyebrow">REFERRAL CARD</div>
                    <div class="kit-copy"><strong>Front</strong>

{{ $kit['referral_card']['front'] }}</div>
                    <div class="kit-copy" style="margin-top:12px;"><strong>Back</strong>

{{ $kit['referral_card']['back'] }}</div>
                </article>

                <article class="kit-card">
                    <div class="kit-eyebrow">STREET PITCH</div>
                    <div class="kit-copy">{{ $kit['street_pitch']['opening'] }}</div>
                    <div class="kit-copy" style="margin-top:10px;">{{ $kit['street_pitch']['middle'] }}</div>
                    <div class="kit-copy" style="margin-top:10px;">{{ $kit['street_pitch']['close'] }}</div>
                </article>

                <article class="kit-card">
                    <div class="kit-eyebrow">PROOF POINTS</div>
                    <ul class="kit-list">
                        @foreach ($kit['proof_points'] as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                </article>

                <article class="kit-card" style="grid-column: 1 / -1;">
                    <div class="kit-eyebrow">PLACEMENT CHECKLIST</div>
                    <ul class="kit-list">
                        @foreach ($kit['placement_checklist'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </article>

                <article class="kit-card" style="grid-column: 1 / -1;">
                    <div class="kit-eyebrow">ASSET VARIANTS</div>
                    <div class="asset-variant-list">
                        @foreach ($kit['asset_variants'] as $variant)
                            <div class="asset-card-mini">
                                <strong>{{ $variant['label'] }}</strong>
                                <div class="kit-muted">{{ $variant['format'] }}</div>
                                <div class="kit-muted">{{ $variant['headline'] }}</div>
                                <div class="kit-actions" style="margin-top:0;">
                                    <a class="kit-btn" href="{{ route('founder.first-100.promo-links.asset', [$promoLink, $variant['key']]) }}">Open asset view</a>
                                    <a class="kit-btn" href="{{ route('founder.first-100.promo-links.asset.svg', [$promoLink, $variant['key']]) }}">Download SVG</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            </section>
        </div>
    </div>
@endsection
