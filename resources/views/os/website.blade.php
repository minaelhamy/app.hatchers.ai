@extends('os.layout')

@section('content')
    @php
        $companyName = $website['company_name'];
        $businessModel = $website['business_model'];
        $websiteStatus = $website['website_status'];
        $recommendedEngine = $website['recommended_engine'];
        $currentWebsiteUrl = $website['current_website_url'];
        $recommendedSubdomain = $website['recommended_subdomain'];
        $customDomainExample = $website['custom_domain_example'];
        $customDomain = $website['custom_domain'];
        $customDomainStatus = $website['custom_domain_status'];
        $engines = $website['engines'];
        $themeOptions = $website['theme_options'];
        $domainModel = $website['domain_model'];
        $nextSteps = $website['next_steps'];
        $recommendedCard = collect($engines)->firstWhere('key', $recommendedEngine) ?: $engines[0];
        $initialThemeOptions = $themeOptions[$recommendedEngine] ?? [];
        $dnsTargets = $website['dns_targets'];
    @endphp

    <div class="sidebar-layout">
        <aside class="sidebar-card">
            <div class="pill">Website Workspace</div>
            <div class="nav-group" style="margin-top: 18px;">
                <div class="nav-group-title">Workspace</div>
                <a class="nav-item" href="/dashboard">Home</a>
                <a class="nav-item" href="#">Weekly Plan</a>
                <a class="nav-item" href="#">Atlas</a>
            </div>
            <div class="nav-group">
                <div class="nav-group-title">Business</div>
                <a class="nav-item active" href="/website">Website</a>
                <a class="nav-item" href="#">Products</a>
                <a class="nav-item" href="#">Services</a>
                <a class="nav-item" href="#">Orders &amp; Bookings</a>
            </div>
            <div class="nav-group">
                <div class="nav-group-title">Growth</div>
                <a class="nav-item" href="#">Marketing</a>
                <a class="nav-item" href="#">Content Studio</a>
            </div>
        </aside>

        <div>
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
                <div class="eyebrow">Unified Website Workspace</div>
                <h1>{{ $companyName }} should publish from one OS, not four disconnected tools.</h1>
                <p class="muted">
                    Hatchers OS is the founder workspace at <strong>app.hatchers.ai</strong>. Bazaar and Servio remain the website engines behind the scenes,
                    but the founder experience should stay centered here.
                </p>
                <div class="cta-row">
                    <span class="pill">Business model: {{ ucfirst($businessModel) }}</span>
                    <span class="pill">Website status: {{ ucfirst(str_replace('_', ' ', $websiteStatus)) }}</span>
                    <span class="pill">Recommended engine: {{ strtoupper($recommendedEngine) }}</span>
                    <span class="pill">Custom domain: {{ ucfirst(str_replace('_', ' ', $customDomainStatus)) }}</span>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Choose Website Path</h2>
                    <p class="muted" style="margin-bottom: 14px;">Hatchers OS chooses the right engine behind the scenes, but the founder should make this choice from one workflow.</p>
                    <form method="POST" action="/website/setup" class="stack">
                        @csrf
                        <div class="stack-item">
                            <strong>Website engine</strong><br>
                            <select name="website_engine" data-engine-select style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                                @foreach ($engines as $engine)
                                    <option value="{{ $engine['key'] }}" @selected(old('website_engine', $recommendedEngine) === $engine['key'])>
                                        {{ $engine['label'] }} · {{ $engine['role'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="stack-item">
                            <strong>Business path</strong><br>
                            <select name="website_mode" style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                                @foreach (['product' => 'Product business', 'service' => 'Service business', 'hybrid' => 'Hybrid business'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('website_mode', $businessModel) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="stack-item">
                            <strong>Website title</strong><br>
                            <input
                                type="text"
                                name="website_title"
                                value="{{ old('website_title', $recommendedCard['website_title']) }}"
                                style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;"
                                placeholder="Enter the public website title">
                        </div>
                        <div class="stack-item">
                            <strong>Theme</strong><br>
                            <select name="theme_template" data-theme-select style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                                @foreach ($initialThemeOptions as $theme)
                                    <option value="{{ $theme['id'] }}" @selected(old('theme_template', $recommendedCard['theme']) === $theme['id'])>
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

                <div class="card">
                    <h2>Publish From Hatchers OS</h2>
                    <p class="muted" style="margin-bottom: 14px;">Publishing should happen from the OS, while Bazaar or Servio handles the storefront engine in the background.</p>
                    <div class="stack">
                        <div class="stack-item">
                            <strong>Recommended public site</strong><br>
                            {{ preg_replace('#^https?://#', '', $recommendedSubdomain) }}
                        </div>
                        <div class="stack-item">
                            <strong>Current website status</strong><br>
                            {{ ucfirst(str_replace('_', ' ', $websiteStatus)) }}
                        </div>
                        <div class="stack-item">
                            <strong>Publishing note</strong><br>
                            Once published, the OS records the site as live and uses the engine's public storefront URL as the founder-facing website.
                        </div>
                    </div>
                    <form method="POST" action="/website/publish" style="margin-top: 18px;">
                        @csrf
                        <input type="hidden" name="website_engine" value="{{ $recommendedEngine }}" data-publish-engine>
                        <button class="btn primary" type="submit">Publish Website</button>
                    </form>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Add Your First Website Item</h2>
                    <p class="muted" style="margin-bottom: 14px;">This lets founders create the first product or service directly from Hatchers OS without jumping into engine dashboards first.</p>
                    <form method="POST" action="/website/starter" class="stack">
                        @csrf
                        <div class="stack-item">
                            <strong>Engine</strong><br>
                            <select name="website_engine" data-starter-engine style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                                @foreach ($engines as $engine)
                                    <option value="{{ $engine['key'] }}" @selected($recommendedEngine === $engine['key'])>{{ $engine['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="stack-item">
                            <strong>Starter type</strong><br>
                            <select name="starter_mode" style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                                <option value="product" @selected($businessModel !== 'service')>First product</option>
                                <option value="service" @selected($businessModel === 'service')>First service</option>
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

                <div class="card">
                    <h2>Connect Custom Domain</h2>
                    <p class="muted" style="margin-bottom: 14px;">This first OS version captures the founder’s custom domain and writes it into the selected engine, then guides DNS setup from the OS.</p>
                    <form method="POST" action="/website/domain" class="stack">
                        @csrf
                        <div class="stack-item">
                            <strong>Engine</strong><br>
                            <select name="website_engine" data-domain-engine style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                                @foreach ($engines as $engine)
                                    <option value="{{ $engine['key'] }}" @selected($recommendedEngine === $engine['key'])>{{ $engine['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="stack-item">
                            <strong>Custom domain</strong><br>
                            <input type="text" name="custom_domain" value="{{ old('custom_domain', $customDomain ?: $customDomainExample) }}" style="margin-top: 10px; width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--line); background: #fff;" placeholder="www.yourbrand.com">
                        </div>
                        <div class="stack-item">
                            <strong>DNS target</strong><br>
                            <span data-dns-target>{{ $dnsTargets[$recommendedEngine] ?? '' }}</span><br>
                            <span class="muted">Create a CNAME for `www` pointing to the engine host above. After DNS propagates, Hatchers can serve the site from your branded domain.</span>
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
            </section>

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
                    <div class="muted">Custom Domain Path</div>
                    <strong style="font-size: 1.15rem;">{{ $customDomainExample }}</strong>
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
                    <h2>OS Website Principles</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>One founder workspace</strong><br>
                            Founders should build and manage from Hatchers OS, even when the underlying engine is Bazaar or Servio.
                        </div>
                        <div class="stack-item">
                            <strong>One publishing language</strong><br>
                            The founder should choose a public website and domain, not choose between separate platform brands.
                        </div>
                        <div class="stack-item">
                            <strong>Engine routing behind the scenes</strong><br>
                            Product-heavy businesses route to Bazaar first, service-heavy businesses route to Servio first, and hybrid businesses can use both.
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
                            <a class="btn primary" href="{{ $engine['admin_url'] }}" target="_blank" rel="noreferrer">
                                Open {{ $engine['label'] }}
                            </a>
                        </div>
                        @if (!empty($engine['updated_at']))
                            <p class="muted" style="margin-top: 14px;">Updated {{ $engine['updated_at'] }}</p>
                        @endif
                    </div>
                @endforeach
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>What We Already Have Working</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Shared intelligence</strong><br>
                            Bazaar and Servio already push snapshots into Hatchers OS, so the founder dashboard can summarize website readiness, revenue, products, services, orders, and bookings.
                        </div>
                        <div class="stack-item">
                            <strong>Cross-platform actions</strong><br>
                            The OS assistant can already create and update real Bazaar and Servio records after explicit confirmation.
                        </div>
                        <div class="stack-item">
                            <strong>Unified founder logic</strong><br>
                            The OS already knows the founder, company, subscription, weekly state, and website engine context.
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>Next OS Build Steps</h2>
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
        </div>
    </div>

    <script>
        (() => {
            const themeMap = @json($themeOptions);
            const dnsTargets = @json($dnsTargets);
            const engineSelect = document.querySelector('select[name="website_engine"]');
            const modeSelect = document.querySelector('select[name="website_mode"]');
            const themeSelect = document.querySelector('[data-theme-select]');
            const themeGrid = document.querySelector('[data-theme-grid]');
            const publishEngine = document.querySelector('[data-publish-engine]');
            const starterEngine = document.querySelector('[data-starter-engine]');
            const domainEngine = document.querySelector('[data-domain-engine]');
            const dnsTarget = document.querySelector('[data-dns-target]');
            const selectedTheme = @json(old('theme_template', $recommendedCard['theme']));

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

            engineSelect.addEventListener('change', () => populateThemes(engineSelect.value));
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
