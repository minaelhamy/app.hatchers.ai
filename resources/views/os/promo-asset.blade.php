@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'promo-kit-page')

@section('head')
    <style>
        .page.promo-kit-page { padding: 0; background: #f7f3eb; }
        .asset-shell { min-height: 100vh; padding: 30px 18px 40px; }
        .asset-wrap { width: min(860px, calc(100vw - 28px)); margin: 0 auto; }
        .asset-stage { background: rgba(255,255,255,0.95); border: 1px solid rgba(220,207,191,0.72); border-radius: 28px; padding: 22px; box-shadow: 0 12px 32px rgba(60,45,28,0.05); }
        .asset-toolbar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
        .asset-btn { display:inline-flex; align-items:center; justify-content:center; padding:11px 16px; border-radius:999px; text-decoration:none; font-weight:600; border:1px solid rgba(220,207,191,0.8); background:#fff; color:#181717; cursor:pointer; }
        .asset-btn.primary { background:#181717; color:#fff; border-color:#181717; }
        .asset-board { border-radius: 28px; padding: 28px; min-height: 620px; display:grid; gap:16px; position:relative; overflow:hidden; }
        .asset-board::after { content:""; position:absolute; inset:auto -60px -60px auto; width:220px; height:220px; border-radius:999px; background:rgba(255,255,255,0.18); }
        .asset-label { font-size:0.82rem; letter-spacing:0.14em; text-transform:uppercase; opacity:0.78; }
        .asset-headline { font-size: clamp(2.2rem, 4vw, 4rem); line-height:0.94; letter-spacing:-0.05em; max-width:12ch; }
        .asset-copy { font-size:1.02rem; line-height:1.65; max-width:36ch; white-space:pre-line; }
        .asset-pill-row { display:flex; gap:8px; flex-wrap:wrap; }
        .asset-pill { display:inline-flex; align-items:center; justify-content:center; padding:8px 11px; border-radius:999px; background:rgba(255,255,255,0.75); color:inherit; font-size:0.84rem; }
        .asset-bottom { margin-top:auto; display:flex; gap:16px; align-items:flex-end; justify-content:space-between; }
        .asset-url-box { padding:14px 16px; border-radius:18px; background:rgba(255,255,255,0.84); }
        .asset-url-line { margin-top:6px; word-break:break-all; line-height:1.5; }
        .asset-qr-svg { width:180px; height:180px; background:#fff; border-radius:18px; padding:10px; display:grid; place-items:center; }
        .asset-qr-svg svg { width:100%; height:100%; }
        @media print {
            .asset-toolbar { display:none; }
            .asset-shell { padding:0; }
            .asset-stage { box-shadow:none; border:none; padding:0; }
        }
    </style>
@endsection

@section('content')
    @php
        $tone = $kit['brand_tone'] ?? ['accent' => '#1f6f78', 'accent_soft' => '#ebfbfd', 'ink' => '#173437'];
        $isPoster = $variant === 'poster';
        $isReferral = $variant === 'referral';
        $isSocial = $variant === 'social';
        $isBusiness = $variant === 'business';
    @endphp
    <div class="asset-shell">
        <div class="asset-wrap">
            <div class="asset-toolbar">
                <button class="asset-btn primary" type="button" onclick="window.print()">Print asset</button>
                <a class="asset-btn" href="{{ route('founder.first-100.promo-links.asset.svg', [$promoLink, $variant]) }}">Download asset SVG</a>
                <a class="asset-btn" href="{{ route('founder.first-100.promo-links.qr-svg', $promoLink) }}">Download QR SVG</a>
                <a class="asset-btn" href="{{ route('founder.first-100.promo-links.qr-png', $promoLink) }}">Download QR PNG</a>
                <a class="asset-btn" href="{{ route('founder.first-100.promo-links.kit', $promoLink) }}">Back to kit</a>
            </div>

            <div class="asset-stage">
                <article class="asset-board" style="
                    background: {{ $isSocial ? $tone['accent'] : 'linear-gradient(180deg, ' . $tone['accent_soft'] . ', #ffffff)' }};
                    color: {{ $isSocial ? '#ffffff' : $tone['ink'] }};
                    border: {{ $isBusiness ? '2px dashed ' . $tone['accent'] : 'none' }};
                ">
                    <div class="asset-label">
                        {{ $isPoster ? 'QR-Ready Flyer' : ($isReferral ? 'Referral Card' : ($isSocial ? 'Share Card' : 'Business Card CTA')) }}
                    </div>

                    <div class="asset-pill-row">
                        <span class="asset-pill" @if($isSocial) style="background:rgba(255,255,255,0.16);color:#fff;" @endif>{{ $kit['source_channel_label'] }}</span>
                        <span class="asset-pill" @if($isSocial) style="background:rgba(255,255,255,0.16);color:#fff;" @endif>Promo {{ $kit['promo_code'] }}</span>
                    </div>

                    <div class="asset-headline">
                        @if ($isPoster || $isSocial)
                            {{ $kit['flyer']['headline'] }}
                        @elseif ($isReferral)
                            {{ $kit['offer_title'] }}
                        @else
                            {{ $kit['cta_label'] }}
                        @endif
                    </div>

                    <div class="asset-copy">
                        @if ($isPoster)
                            {{ $kit['flyer']['subheadline'] }}
                        @elseif ($isReferral)
                            {{ $kit['referral_card']['front'] }}
                        @elseif ($isSocial)
                            {{ $kit['flyer']['body'] }}
                        @else
                            {{ $kit['street_pitch']['opening'] }}
                        @endif
                    </div>

                    <div class="asset-pill-row">
                        @foreach (array_slice($kit['proof_points'], 0, 3) as $point)
                            <span class="asset-pill" @if($isSocial) style="background:rgba(255,255,255,0.16);color:#fff;" @endif>{{ $point }}</span>
                        @endforeach
                    </div>

                    <div class="asset-bottom">
                        <div class="asset-url-box" @if($isSocial) style="background:rgba(255,255,255,0.16);color:#fff;" @endif>
                            <strong>{{ $kit['cta_label'] }}</strong>
                            <div class="asset-url-line">{{ $kit['promo_url'] }}</div>
                        </div>
                        <div class="asset-qr-svg">
                            {!! $qrSvg !!}
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </div>
@endsection
