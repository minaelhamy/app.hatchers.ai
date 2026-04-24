@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'public-website-page')

@section('head')
    <style>
        .page.public-website-page { padding: 0; background: #f7f3eb; }
        .site-shell { min-height: 100vh; }
        .site-hero { padding: 32px 24px 18px; background: linear-gradient(180deg, rgba(255,252,247,0.98), rgba(247,243,235,0.95)); border-bottom: 1px solid rgba(220,207,191,0.65); }
        .site-wrap { width: min(1100px, calc(100vw - 40px)); margin: 0 auto; }
        .site-eyebrow { font-size: 0.82rem; letter-spacing: 0.14em; color: #7d6b56; margin-bottom: 14px; }
        .site-brand { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
        .site-brand img { width: 164px; height: auto; display: block; }
        .site-host { color: #776956; font-size: 0.94rem; }
        .site-hero-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr); gap: 20px; align-items: start; }
        .site-hero h1 { font-size: clamp(2.3rem, 4vw, 4.6rem); line-height: 0.95; margin: 0 0 14px; letter-spacing: -0.04em; }
        .site-hero p { color: #625848; font-size: 1.02rem; line-height: 1.65; margin: 0 0 16px; max-width: 60ch; }
        .site-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 18px; }
        .site-cta { display: inline-flex; align-items: center; justify-content: center; padding: 13px 18px; border-radius: 999px; text-decoration: none; font-weight: 600; }
        .site-cta.primary { background: #181717; color: #fff; }
        .site-cta.secondary { background: rgba(255,255,255,0.92); color: #181717; border: 1px solid rgba(220,207,191,0.8); }
        .site-panel, .site-card { background: rgba(255,255,255,0.94); border: 1px solid rgba(220,207,191,0.72); border-radius: 24px; box-shadow: 0 12px 32px rgba(60,45,28,0.05); }
        .site-panel { padding: 20px; }
        .site-section { padding: 22px 24px 28px; }
        .site-metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 18px; }
        .site-metric { padding: 16px 18px; }
        .site-metric strong { display: block; font-size: 1.4rem; margin-top: 6px; }
        .site-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 18px; }
        .site-offers { display: grid; gap: 14px; }
        .site-offer-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .site-card { padding: 18px; }
        .site-card .meta { color: #756955; font-size: 0.94rem; margin-top: 8px; line-height: 1.5; }
        .site-status { display: inline-flex; margin-top: 12px; padding: 7px 11px; border-radius: 999px; background: rgba(216,44,96,0.10); color: #bf245e; font-size: 0.85rem; }
        .site-price { margin-top: 14px; font-size: 1.18rem; font-weight: 700; }
        .site-proof { display: grid; gap: 12px; }
        .site-proof-item { padding: 16px 18px; border-radius: 18px; background: rgba(255,255,255,0.92); border: 1px solid rgba(220,207,191,0.7); }
        .site-footer { padding: 0 24px 34px; color: #6d604f; }
        @media (max-width: 960px) {
            .site-hero-grid, .site-grid { grid-template-columns: 1fr; }
            .site-metrics, .site-offer-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 640px) {
            .site-brand { align-items: flex-start; flex-direction: column; }
            .site-metrics, .site-offer-grid { grid-template-columns: 1fr; }
        }
    </style>
@endsection

@section('content')
    @php
        $siteTitle = $site['title'];
        $hero = $site['hero'];
        $metrics = $site['metrics'];
        $offers = $site['offers'];
        $proof = $site['proof'];
        $operations = $site['operations'];
        $contact = $site['contact'];
        $productOffers = collect($offers)->where('type', 'product')->values();
        $serviceOffers = collect($offers)->where('type', 'service')->values();
    @endphp

    <div class="site-shell">
        <section class="site-hero">
            <div class="site-wrap">
                @if (session('success'))
                    <div class="site-panel" style="margin-bottom:16px;border-color:rgba(44,122,87,0.22);background:rgba(44,122,87,0.08);">
                        <strong style="color:#2c7a57;">Request received</strong>
                        <div class="meta" style="margin-top:8px;">{{ session('success') }}</div>
                    </div>
                @endif
                @if (session('error'))
                    <div class="site-panel" style="margin-bottom:16px;border-color:rgba(179,34,83,0.22);background:rgba(179,34,83,0.08);">
                        <strong style="color:#bf245e;">Something needs attention</strong>
                        <div class="meta" style="margin-top:8px;">{{ session('error') }}</div>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="site-panel" style="margin-bottom:16px;border-color:rgba(154,107,27,0.22);background:rgba(154,107,27,0.08);">
                        <strong style="color:#9a6b1b;">A few fields still need attention</strong>
                        <div class="meta" style="margin-top:8px;">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="site-brand">
                    <img src="/brand/hatchers-ai-logo.png" alt="Hatchers AI">
                    <div class="site-host">Published from Hatchers Ai Business OS</div>
                </div>

                <div class="site-hero-grid">
                    <div>
                        <div class="site-eyebrow">{{ $hero['eyebrow'] }}</div>
                        <h1>{{ $hero['headline'] }}</h1>
                        <p>{{ $hero['subhead'] }}</p>
                        <p>{{ $hero['brief'] }}</p>
                        <div class="site-actions">
                            <a href="#offers" class="site-cta primary">{{ $hero['primary_cta'] }}</a>
                            <a href="#contact" class="site-cta secondary">{{ $hero['secondary_cta'] }}</a>
                        </div>
                    </div>

                    <div class="site-panel">
                        <div style="font-size:0.82rem;letter-spacing:0.12em;color:#7d6b56;">LIVE WEBSITE</div>
                        <div style="font-size:1.6rem;font-weight:700;margin-top:10px;">{{ $siteTitle }}</div>
                        <div class="meta" style="margin-top:10px;">Running on {{ strtoupper($site['engine']) }} backend infrastructure while staying published under the Hatchers OS domain model.</div>
                        @if (!empty($site['updated_at']))
                            <div class="meta" style="margin-top:12px;">Last synced {{ $site['updated_at'] }}</div>
                        @endif
                        <div class="site-status">{{ ucfirst($site['business_model']) }} business</div>
                    </div>
                </div>

                <div class="site-metrics">
                    @foreach ($metrics as $metric)
                        <div class="site-card site-metric">
                            <div style="color:#756955;">{{ $metric['label'] }}</div>
                            <strong>{{ $metric['value'] }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="offers" class="site-section">
            <div class="site-wrap site-grid">
                <div class="site-offers">
                    <div>
                        <div class="site-eyebrow">AVAILABLE NOW</div>
                        <h2 style="font-size:2rem;letter-spacing:-0.03em;margin:0 0 8px;">Offers running through the OS</h2>
                        <p style="color:#625848;max-width:62ch;">This public website is rendered inside `app.hatchers.ai`, while the founder keeps managing products, services, orders, and bookings from the OS workspace.</p>
                    </div>

                    <div class="site-offer-grid">
                        @foreach ($offers as $offer)
                            <article class="site-card">
                                <div class="site-eyebrow" style="margin-bottom:10px;">{{ strtoupper($offer['type']) }}</div>
                                <div style="font-size:1.18rem;font-weight:700;">{{ $offer['title'] }}</div>
                                <div class="meta">{{ $offer['meta'] }}</div>
                                @if (!empty($offer['details']))
                                    <div class="meta" style="margin-top:10px;">
                                        @foreach ($offer['details'] as $detail)
                                            <div>{{ $detail }}</div>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($offer['price'] !== '')
                                    <div class="site-price">{{ $offer['price'] }}</div>
                                @endif
                                <div class="site-status">{{ $offer['status'] }}</div>
                            </article>
                        @endforeach
                    </div>
                </div>

                <aside class="site-proof">
                    @foreach ($proof as $item)
                        <div class="site-proof-item">
                            <div style="font-weight:700;">{{ $item['title'] }}</div>
                            <div class="meta">{{ $item['description'] }}</div>
                        </div>
                    @endforeach
                </aside>
            </div>
        </section>

        @if ($productOffers->isNotEmpty() || $serviceOffers->isNotEmpty())
            <section class="site-section" style="padding-top: 0;">
                <div class="site-wrap">
                    <div class="site-eyebrow">REQUEST NOW</div>
                    <h2 style="font-size:2rem;letter-spacing:-0.03em;margin:0 0 16px;">Start directly from this OS website</h2>
                    <div class="site-offer-grid">
                        @foreach ($productOffers as $offer)
                            <div class="site-card">
                                <div style="font-size:1.1rem;font-weight:700;">Order {{ $offer['title'] }}</div>
                                <div class="meta" style="margin-top:8px;">This creates a real Bazaar order for the founder to manage inside Hatchers Ai Business OS.</div>
                                <form method="POST" action="{{ route('public.website.order', ['websitePath' => $site['path']]) }}" style="margin-top:14px;display:grid;gap:12px;">
                                    @csrf
                                    <input type="hidden" name="offer_title" value="{{ $offer['title'] }}">
                                    @if (!empty($offer['request_options']['variants']))
                                        <select name="selected_variant" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                            <option value="">Choose a variant</option>
                                            @foreach ($offer['request_options']['variants'] as $variant)
                                                <option value="{{ $variant['name'] }}">{{ $variant['name'] }} · {{ $variant['qty'] }} in stock</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    @if (!empty($offer['request_options']['extras']))
                                        <div class="meta">Extras</div>
                                        <div style="display:grid;gap:8px;">
                                            @foreach ($offer['request_options']['extras'] as $extra)
                                                <label style="display:flex;align-items:center;gap:8px;color:#625848;">
                                                    <input type="checkbox" name="selected_extras[]" value="{{ $extra['name'] }}">
                                                    <span>{{ $extra['name'] }} (+{{ $offer['price'] !== '' ? '' : '' }}{{ number_format((float) $extra['price'], 2) }})</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                    <input type="text" name="customer_name" placeholder="Your name" value="{{ old('customer_name') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                        <input type="email" name="customer_email" placeholder="Email" value="{{ old('customer_email') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                        <input type="text" name="customer_mobile" placeholder="Mobile" value="{{ old('customer_mobile') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                    </div>
                                    <input type="number" min="1" max="99" name="quantity" placeholder="Quantity" value="{{ old('quantity', 1) }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                    <input type="text" name="address" placeholder="Address" value="{{ old('address') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                        <input type="text" name="building" placeholder="Building" value="{{ old('building') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                        <input type="text" name="landmark" placeholder="Landmark" value="{{ old('landmark') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                        <input type="text" name="postal_code" placeholder="Postal code" value="{{ old('postal_code') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                        <input type="text" name="delivery_area" placeholder="Delivery area" value="{{ old('delivery_area') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                    </div>
                                    <textarea name="notes" rows="4" placeholder="Order notes" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">{{ old('notes') }}</textarea>
                                    <button type="submit" class="site-cta primary" style="border:none;cursor:pointer;">Send order request</button>
                                </form>
                            </div>
                        @endforeach

                        @foreach ($serviceOffers as $offer)
                            <div class="site-card">
                                <div style="font-size:1.1rem;font-weight:700;">Book {{ $offer['title'] }}</div>
                                <div class="meta" style="margin-top:8px;">This creates a real Servio booking for the founder to schedule and manage inside Hatchers Ai Business OS.</div>
                                <form method="POST" action="{{ route('public.website.booking', ['websitePath' => $site['path']]) }}" style="margin-top:14px;display:grid;gap:12px;">
                                    @csrf
                                    <input type="hidden" name="offer_title" value="{{ $offer['title'] }}">
                                    @if (!empty($offer['request_options']['additional_services']))
                                        <div class="meta">Add-ons</div>
                                        <div style="display:grid;gap:8px;">
                                            @foreach ($offer['request_options']['additional_services'] as $extra)
                                                <label style="display:flex;align-items:center;gap:8px;color:#625848;">
                                                    <input type="checkbox" name="selected_additional_services[]" value="{{ $extra['name'] }}">
                                                    <span>{{ $extra['name'] }} (+{{ number_format((float) $extra['price'], 2) }})</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                    <input type="text" name="customer_name" placeholder="Your name" value="{{ old('customer_name') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                        <input type="email" name="customer_email" placeholder="Email" value="{{ old('customer_email') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                        <input type="text" name="customer_mobile" placeholder="Mobile" value="{{ old('customer_mobile') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                                        <input type="date" name="booking_date" value="{{ old('booking_date') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                        <input type="time" name="booking_time" value="{{ old('booking_time') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                        <input type="time" name="booking_endtime" value="{{ old('booking_endtime') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                    </div>
                                    <input type="text" name="address" placeholder="Address (optional)" value="{{ old('address') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                        <input type="text" name="city" placeholder="City" value="{{ old('city') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                        <input type="text" name="state" placeholder="State" value="{{ old('state') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                                        <input type="text" name="country" placeholder="Country" value="{{ old('country') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                        <input type="text" name="postal_code" placeholder="Postal code" value="{{ old('postal_code') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                        <input type="text" name="landmark" placeholder="Landmark" value="{{ old('landmark') }}" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">
                                    </div>
                                    <textarea name="notes" rows="4" placeholder="Booking notes" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(220,207,191,0.9);background:#fff;">{{ old('notes') }}</textarea>
                                    <button type="submit" class="site-cta primary" style="border:none;cursor:pointer;">Send booking request</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if (!empty($operations))
            <section class="site-section" style="padding-top: 0;">
                <div class="site-wrap">
                    <div class="site-eyebrow">HOW THIS BUSINESS OPERATES</div>
                    <h2 style="font-size:2rem;letter-spacing:-0.03em;margin:0 0 16px;">Live operating signals from the OS</h2>
                    <div class="site-offer-grid">
                        @foreach ($operations as $operation)
                            <div class="site-card">
                                <div style="font-size:1.1rem;font-weight:700;">{{ $operation['title'] }}</div>
                                <div class="meta" style="margin-top: 12px;">
                                    @foreach ($operation['items'] as $item)
                                        <div style="margin-bottom: 12px;">
                                            <strong style="display:block;color:#181717;">{{ $item['title'] }}</strong>
                                            <span>{{ $item['meta'] }}</span>
                                            @if (!empty($item['detail']))
                                                <div>{{ $item['detail'] }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <footer id="contact" class="site-footer">
            <div class="site-wrap site-card" style="padding:22px 24px;">
                <div class="site-eyebrow">CONTACT</div>
                <div style="font-size:1.35rem;font-weight:700;">{{ $contact['company'] }}</div>
                <div class="meta" style="margin-top:10px;">
                    @if ($contact['founder_name'] !== '')
                        Founder: {{ $contact['founder_name'] }}<br>
                    @endif
                    @if ($contact['email'] !== '')
                        Email: <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
                    @endif
                </div>
            </div>
        </footer>
    </div>
@endsection
