<?php

namespace App\Services;

use App\Models\Founder;
use App\Models\FounderLead;
use App\Models\FounderBusinessBrief;
use App\Models\FounderConversationThread;
use App\Models\FounderFirstHundredTracker;
use App\Models\FounderIcpProfile;
use App\Models\FounderLeadChannel;
use App\Models\FounderPricingRecommendation;
use App\Models\FounderLaunchSystem;
use App\Models\FounderPod;
use App\Models\VerticalBlueprint;
use App\Models\FounderPromoLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FounderRevenueOsService
{
    public function dashboard(Founder $founder): array
    {
        $company = $founder->company;
        $blueprint = $company?->verticalBlueprint;
        $brief = $founder->businessBrief;
        $icp = $founder->icpProfiles()->latest()->first();
        $leads = $founder->leads()->latest()->get();

        $won = $leads->where('lead_stage', 'won');
        $active = $leads->filter(fn (FounderLead $lead) => !in_array((string) $lead->lead_stage, ['won', 'lost'], true));
        $followUpDue = $leads->filter(fn (FounderLead $lead) => $lead->next_follow_up_at && $lead->next_follow_up_at->lte(now()) && !in_array((string) $lead->lead_stage, ['won', 'lost'], true));

        $channelPerformance = $this->channelPerformance($leads);
        $bestChannel = $channelPerformance[0] ?? null;
        $topOffer = $this->topOffer($leads);
        $websiteStatus = (string) ($company?->website_status ?? '');
        $websiteGenerationStatus = (string) ($company?->website_generation_status ?? '');
        $acquisitionEngine = $this->acquisitionEngine($founder, $blueprint, $brief, $icp, $leads, $bestChannel);
        $dailyPlan = $this->dailyPlan(
            $founder,
            $blueprint,
            $brief,
            $icp,
            $brief?->location_city ?: (string) ($company?->primary_city ?? ''),
            (string) ($icp?->primary_icp_name ?? ''),
            $websiteStatus,
            $websiteGenerationStatus,
            $leads,
            $followUpDue,
            $bestChannel
        );

        $this->syncLeadChannels($founder, $blueprint, $acquisitionEngine['playbooks'] ?? []);
        $this->syncFirstHundredTracker($founder, $blueprint, [
            'identified_leads' => $leads->count(),
            'active_conversations' => $active->count(),
            'customers_won' => $won->count(),
            'follow_up_due' => $followUpDue->count(),
            'first_hundred_progress_percent' => min(100, (int) round(($won->count() / 100) * 100)),
        ], $dailyPlan, $acquisitionEngine, $bestChannel);

        return [
            'metrics' => [
                'identified_leads' => $leads->count(),
                'active_conversations' => $active->count(),
                'customers_won' => $won->count(),
                'follow_up_due' => $followUpDue->count(),
                'first_hundred_progress_percent' => min(100, (int) round(($won->count() / 100) * 100)),
                'estimated_pipeline_value' => round((float) $active->sum(fn (FounderLead $lead) => (float) ($lead->estimated_value ?? 0)), 2),
            ],
            'milestones' => $this->milestones($won->count()),
            'channel_performance' => $channelPerformance,
            'best_channel' => $bestChannel,
            'best_offer' => $topOffer,
            'acquisition_engine' => $acquisitionEngine,
            'daily_plan' => $dailyPlan,
        ];
    }

    public function workspace(Founder $founder, array $filters = []): array
    {
        $dashboard = $this->dashboard($founder);
        $company = $founder->company;
        $blueprint = $company?->verticalBlueprint;
        $brief = $founder->businessBrief;
        $icp = $founder->icpProfiles()->latest()->first();
        $normalized = [
            'stage' => (string) ($filters['stage'] ?? 'all'),
            'channel' => (string) ($filters['channel'] ?? 'all'),
            'q' => trim((string) ($filters['q'] ?? '')),
        ];

        $query = $founder->leads()->latest();

        if ($normalized['stage'] !== '' && $normalized['stage'] !== 'all') {
            $query->where('lead_stage', $normalized['stage']);
        }

        if ($normalized['channel'] !== '' && $normalized['channel'] !== 'all') {
            $query->where('lead_channel', $normalized['channel']);
        }

        if ($normalized['q'] !== '') {
            $query->where(function ($builder) use ($normalized): void {
                $builder
                    ->where('lead_name', 'like', '%' . $normalized['q'] . '%')
                    ->orWhere('contact_handle', 'like', '%' . $normalized['q'] . '%')
                    ->orWhere('offer_name', 'like', '%' . $normalized['q'] . '%')
                    ->orWhere('source_notes', 'like', '%' . $normalized['q'] . '%');
            });
        }

        $leads = $query->get()->map(function (FounderLead $lead): array {
            return [
                'id' => $lead->id,
                'lead_name' => (string) $lead->lead_name,
                'lead_channel' => (string) $lead->lead_channel,
                'lead_channel_label' => $this->labelize($lead->lead_channel),
                'lead_stage' => (string) $lead->lead_stage,
                'lead_stage_label' => $this->labelize($lead->lead_stage),
                'contact_handle' => (string) ($lead->contact_handle ?? ''),
                'city' => (string) ($lead->city ?? ''),
                'offer_name' => (string) ($lead->offer_name ?? ''),
                'estimated_value' => (float) ($lead->estimated_value ?? 0),
                'estimated_value_display' => number_format((float) ($lead->estimated_value ?? 0), 2),
                'source_notes' => (string) ($lead->source_notes ?? ''),
                'stage_notes' => (string) ($lead->stage_notes ?? ''),
                'first_contacted_at' => optional($lead->first_contacted_at)->toDayDateTimeString(),
                'last_followed_up_at' => optional($lead->last_followed_up_at)->toDayDateTimeString(),
                'next_follow_up_at' => optional($lead->next_follow_up_at)->toDayDateTimeString(),
                'converted_at' => optional($lead->converted_at)->toDayDateTimeString(),
                'lost_at' => optional($lead->lost_at)->toDayDateTimeString(),
                'is_follow_up_due' => $lead->next_follow_up_at ? $lead->next_follow_up_at->lte(now()) : false,
                'conversation_pack' => $this->conversationPack($lead, $founder, $blueprint, $brief, $icp),
            ];
        })->values()->all();

        $this->syncConversationThreads($founder, $query->get());

        return [
            'summary' => $dashboard,
            'filters' => $normalized,
            'leads' => $leads,
            'stage_options' => $this->stageOptions(),
            'channel_options' => $this->channelOptions($founder->company?->verticalBlueprint),
            'conversation_engine' => $this->conversationEngine($founder, $blueprint, $brief, $icp, $leads),
            'follow_up_engine' => $this->followUpEngine($founder, $query->get()),
            'offline_bridge' => $this->offlineBridge($founder, $blueprint, $brief, $icp),
        ];
    }

    public function pricingOptimizer(Founder $founder, array $offers = []): array
    {
        $company = $founder->company;
        $blueprint = $company?->verticalBlueprint;
        $brief = $founder->businessBrief;
        $icp = $founder->icpProfiles()->latest()->first();
        $leadRows = $this->workspace($founder)['leads'];
        $bestOffer = $this->topOffer($founder->leads()->get());
        $bestChannel = $this->channelPerformance($founder->leads()->get())[0] ?? null;

        $currentOffers = collect($offers)->filter(fn (array $offer) => trim((string) ($offer['title'] ?? '')) !== '')->values();
        $primaryOffer = $currentOffers->first();
        $businessModel = (string) ($company?->business_model ?? ($blueprint?->business_model ?? 'service'));
        $currency = strtoupper((string) ($founder->commercialSummary?->currency ?? 'USD'));
        $icpName = trim((string) ($icp?->primary_icp_name ?? 'your ideal customer'));
        $problem = trim((string) ($brief?->problem_solved ?? $company?->company_brief ?? 'the problem you solve'));
        $pricingDefaults = is_array($blueprint?->pricing_preset_json) && !empty($blueprint->pricing_preset_json)
            ? $blueprint->pricing_preset_json
            : (is_array($blueprint?->default_pricing_json) ? $blueprint->default_pricing_json : []);
        $upsellDefaults = is_array($blueprint?->default_offer_json['upsells'] ?? null) ? $blueprint->default_offer_json['upsells'] : [];
        $channels = collect(is_array($blueprint?->default_channels_json) ? $blueprint->default_channels_json : [])->filter()->values();

        $anchorTitle = trim((string) ($primaryOffer['title'] ?? ($pricingDefaults['tier_2'] ?? $pricingDefaults['tier_1'] ?? 'Core offer')));
        $entryTitle = trim((string) ($pricingDefaults['tier_1'] ?? 'Starter offer'));
        $premiumTitle = trim((string) ($pricingDefaults['tier_3'] ?? 'Premium offer'));
        $basePrice = max(1, (float) ($primaryOffer['price'] ?? 0));
        if ($basePrice <= 1) {
            $basePrice = $businessModel === 'product' ? 25 : 35;
        }

        $starterPrice = $this->roundOfferPrice(max(5, $basePrice * 0.7), $businessModel);
        $corePrice = $this->roundOfferPrice($basePrice, $businessModel);
        $premiumPrice = $this->roundOfferPrice(max($basePrice * 1.55, $starterPrice + 10), $businessModel);

        $bundleLogic = $businessModel === 'product'
            ? 'Bundle 2-3 related items so the average order value rises without asking the buyer for a totally new decision.'
            : 'Package the service into a repeat plan so the buyer chooses consistency, not a one-off appointment.';

        $priceStory = 'For ' . $icpName . ', price clarity should remove hesitation around ' . $problem . '. Lead with the easy first step, anchor with the core offer, then make the premium option feel like the obvious upgrade.';

        $recommendations = [
            [
                'key' => 'entry-offer',
                'title' => $entryTitle,
                'price' => $starterPrice,
                'positioning' => 'Entry offer',
                'description' => 'Use this as the easiest first yes. It should reduce friction and get the first customer moving quickly.',
            ],
            [
                'key' => 'core-profit-offer',
                'title' => $anchorTitle,
                'price' => $corePrice,
                'positioning' => 'Core profit offer',
                'description' => 'This is the main offer you want most buyers to choose. It should be the clearest answer to the main problem.',
            ],
            [
                'key' => 'premium-upgrade',
                'title' => $premiumTitle,
                'price' => $premiumPrice,
                'positioning' => 'Premium upgrade',
                'description' => 'This adds convenience, speed, or stronger transformation for buyers who want the best version.',
            ],
        ];

        $upsells = collect($upsellDefaults)->map(function ($upsell, int $index) use ($basePrice, $businessModel): array {
            $amount = $this->roundOfferPrice(max(5, $basePrice * (0.18 + ($index * 0.06))), $businessModel);

            return [
                'title' => (string) $upsell,
                'price' => $amount,
                'why' => 'Attach this after the buyer already said yes so it feels like a simple enhancement, not a second sale.',
            ];
        })->take(3)->values()->all();

        $storedRecommendations = $this->syncPricingRecommendations(
            $founder,
            $blueprint,
            $currentOffers->all(),
            $currency,
            $recommendations,
            [
                'best_offer_name' => $bestOffer['offer_name'] ?? ($primaryOffer['title'] ?? ''),
                'best_channel_label' => $bestChannel['channel_label'] ?? ($channels->first() ?: 'your best-fit local channel'),
                'bundle_logic' => $bundleLogic,
                'price_story' => $priceStory,
            ]
        );

        return [
            'headline' => 'Offer & Pricing Optimizer',
            'currency' => $currency,
            'price_story' => $priceStory,
            'best_offer_name' => $bestOffer['offer_name'] ?? ($primaryOffer['title'] ?? ''),
            'best_channel_label' => $bestChannel['channel_label'] ?? ($channels->first() ?: 'your best-fit local channel'),
            'bundle_logic' => $bundleLogic,
            'recommendations' => $storedRecommendations,
            'upsells' => $upsells,
            'conversion_notes' => [
                'Lead with one clear starter option before showing everything else.',
                'Use the middle offer as the most obvious choice for ' . $icpName . '.',
                'Present upsells only after the core decision feels easy.',
            ],
        ];
    }

    public function podsWorkspace(Founder $founder): array
    {
        $company = $founder->company;
        $pods = FounderPod::query()
            ->with(['verticalBlueprint', 'memberships.founder.company', 'posts.founder.company'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->filter(function (FounderPod $pod) use ($company): bool {
                if (!$company) {
                    return false;
                }

                if ($pod->vertical_blueprint_id && (int) $pod->vertical_blueprint_id !== (int) $company->vertical_blueprint_id) {
                    return false;
                }

                return true;
            })
            ->map(function (FounderPod $pod) use ($founder): array {
                $members = $pod->memberships->where('status', 'active')->values();
                $posts = $pod->posts->sortByDesc('created_at')->take(8)->values();
                $membership = $members->firstWhere('founder_id', $founder->id);

                return [
                    'id' => $pod->id,
                    'name' => (string) $pod->name,
                    'description' => (string) ($pod->description ?? ''),
                    'stage' => (string) ($pod->stage ?? ''),
                    'city' => (string) ($pod->city ?? ''),
                    'member_count' => $members->count(),
                    'joined' => $membership !== null,
                    'benchmark' => is_array($pod->benchmark_json) ? $pod->benchmark_json : [],
                    'wins_count' => $posts->where('post_type', 'win')->count(),
                    'blockers_count' => $posts->where('post_type', 'blocker')->count(),
                    'members' => $members->take(6)->map(fn ($member): array => [
                        'name' => (string) ($member->founder?->full_name ?? 'Founder'),
                        'company_name' => (string) ($member->founder?->company?->company_name ?? ''),
                    ])->all(),
                    'posts' => $posts->map(fn ($post): array => [
                        'type' => (string) $post->post_type,
                        'title' => (string) $post->title,
                        'body' => (string) $post->body,
                        'founder_name' => (string) ($post->founder?->full_name ?? 'Founder'),
                        'company_name' => (string) ($post->founder?->company?->company_name ?? ''),
                        'created_at' => optional($post->created_at)->toDayDateTimeString(),
                    ])->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'pods' => $pods,
            'metrics' => [
                'available_pods' => count($pods),
                'joined_pods' => collect($pods)->where('joined', true)->count(),
                'shared_wins' => collect($pods)->sum('wins_count'),
                'shared_blockers' => collect($pods)->sum('blockers_count'),
            ],
        ];
    }

    public function promoSourceChannels(): array
    {
        return ['flyer', 'qr_poster', 'referral_card', 'window_sign', 'event_booth', 'business_card', 'neighborhood_board'];
    }

    public function promoKit(Founder $founder, FounderPromoLink $promoLink): array
    {
        $company = $founder->company;
        $brief = $founder->businessBrief;
        $icp = $founder->icpProfiles()->latest()->first();
        $blueprint = $company?->verticalBlueprint;
        $promoStats = $this->promoLinkStats($founder, (string) $promoLink->promo_code, (string) $promoLink->source_channel);

        $problem = trim((string) ($brief?->problem_solved ?? $company?->company_brief ?? 'the problem you solve'));
        $offer = trim((string) ($promoLink->offer_title ?: $brief?->core_offer ?: $company?->intelligence?->core_offer ?: 'your offer'));
        $cta = trim((string) ($promoLink->cta_label ?: 'Scan to get started'));
        $icpName = trim((string) ($icp?->primary_icp_name ?? 'your ideal local customer'));
        $city = trim((string) ($brief?->location_city ?? $company?->primary_city ?? 'your area'));
        $differentiators = collect(is_array($brief?->differentiators_json) ? $brief->differentiators_json : [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->take(3)
            ->values();
        $latestRun = $company?->websiteGenerationRuns()->latest('id')->first();
        $output = is_array($latestRun?->output_json) ? $latestRun->output_json : [];
        $heroImageUrl = $this->promoAssetImageUrl($output);
        $path = trim((string) ($company?->website_path ?? ''), '/');
        $baseUrl = 'https://app.hatchers.ai/' . ($path !== '' ? $path : 'your-business');
        $query = http_build_query(array_filter([
            'src' => $promoLink->source_channel,
            'promo' => $promoLink->promo_code,
            'offer' => $promoLink->offer_title,
        ]));
        $promoUrl = $baseUrl . ($query !== '' ? '?' . $query : '');
        $sourceLabel = $this->labelize((string) $promoLink->source_channel);
        $verticalLabel = trim((string) ($blueprint?->label ?? $company?->vertical_name ?? $company?->business_model ?? 'Local business'));
        $socialProof = $differentiators->isNotEmpty()
            ? $differentiators->all()
            : ['Fast response', 'Clear pricing', 'Easy first step'];
        $brandTone = match ((string) ($company?->business_model ?? 'service')) {
            'product' => ['accent' => '#b45309', 'accent_soft' => '#fff4df', 'ink' => '#2f2415'],
            'hybrid' => ['accent' => '#8e1c74', 'accent_soft' => '#fff0f9', 'ink' => '#301727'],
            default => ['accent' => '#1f6f78', 'accent_soft' => '#ebfbfd', 'ink' => '#173437'],
        };

        return [
            'title' => (string) $promoLink->title,
            'source_channel_label' => $sourceLabel,
            'promo_code' => (string) $promoLink->promo_code,
            'offer_title' => $offer,
            'cta_label' => $cta,
            'promo_url' => $promoUrl,
            'vertical_label' => $verticalLabel,
            'stats' => $promoStats,
            'brand_tone' => $brandTone,
            'hero_image_url' => $heroImageUrl,
            'flyer' => [
                'headline' => 'Need help with ' . $problem . '?',
                'subheadline' => 'A simple way for ' . $icpName . ' in ' . $city . ' to get started with ' . $offer . '.',
                'body' => 'If you want a fast first step without a long back-and-forth, use this link and mention promo code ' . $promoLink->promo_code . '.',
                'cta' => $cta,
            ],
            'referral_card' => [
                'front' => 'Know someone in ' . $city . ' who needs help with ' . $problem . '? Share this offer: ' . $offer . '.',
                'back' => 'Use promo code ' . $promoLink->promo_code . ' and go to ' . $promoUrl,
            ],
            'street_pitch' => [
                'opening' => 'Hi, we help ' . $icpName . ' in ' . $city . ' with ' . $problem . '.',
                'middle' => 'Our easiest first step is ' . $offer . ', and this card gives them a direct link plus promo code ' . $promoLink->promo_code . '.',
                'close' => 'If that sounds useful, they can scan or type the link and get started right away.',
            ],
            'proof_points' => $socialProof->all(),
            'placement_checklist' => [
                'Put this on every ' . strtolower($sourceLabel) . ' asset so the OS can track that source separately.',
                'Keep one clear CTA and one clear promo code on the asset.',
                'Mention the offer title exactly as shown here so founder lead tracking stays consistent.',
                'Send people to the promo URL instead of the generic homepage whenever possible.',
            ],
            'asset_variants' => [
                [
                    'key' => 'poster',
                    'label' => 'QR-ready flyer',
                    'format' => 'A4 / poster',
                    'headline' => 'Lead with the problem, proof, and one CTA.',
                ],
                [
                    'key' => 'referral',
                    'label' => 'Referral card',
                    'format' => 'Pocket card',
                    'headline' => 'Hand this to customers, neighbors, and local partners.',
                ],
                [
                    'key' => 'social',
                    'label' => 'Share card',
                    'format' => 'Story / square post',
                    'headline' => 'Use this as the matching online version of the offline campaign.',
                ],
                [
                    'key' => 'business',
                    'label' => 'Business card CTA',
                    'format' => 'Mini handout',
                    'headline' => 'Simple card with promo, short pitch, and tracked URL.',
                ],
            ],
        ];
    }

    public function promoAssetSvg(array $kit, string $variant): string
    {
        $allowed = ['poster', 'referral', 'social', 'business'];
        if (!in_array($variant, $allowed, true)) {
            $variant = 'poster';
        }

        $accent = (string) ($kit['brand_tone']['accent'] ?? '#1f6f78');
        $accentSoft = (string) ($kit['brand_tone']['accent_soft'] ?? '#ebfbfd');
        $ink = (string) ($kit['brand_tone']['ink'] ?? '#173437');
        $headline = $variant === 'referral'
            ? (string) ($kit['offer_title'] ?? '')
            : ($variant === 'business' ? (string) ($kit['cta_label'] ?? '') : (string) ($kit['flyer']['headline'] ?? ''));
        $body = match ($variant) {
            'referral' => (string) ($kit['referral_card']['front'] ?? ''),
            'social' => (string) ($kit['flyer']['body'] ?? ''),
            'business' => (string) ($kit['street_pitch']['opening'] ?? ''),
            default => (string) ($kit['flyer']['subheadline'] ?? ''),
        };
        $source = (string) ($kit['source_channel_label'] ?? '');
        $promo = (string) ($kit['promo_code'] ?? '');
        $cta = (string) ($kit['cta_label'] ?? '');
        $url = (string) ($kit['promo_url'] ?? '');
        $points = collect((array) ($kit['proof_points'] ?? []))->take(3)->values()->all();
        $textColor = $variant === 'social' ? '#ffffff' : $ink;
        $headlineSvg = $this->svgTextBlock($headline, 72, 112, 620, 52, 700, $textColor);
        $bodySvg = $this->svgTextBlock($body, 72, 270, 540, 26, 400, $textColor);
        $pointChips = '';
        $chipX = 72;
        foreach ($points as $point) {
            $point = trim((string) $point);
            if ($point === '') {
                continue;
            }
            $width = max(110, min(240, 22 + (strlen($point) * 7)));
            $pointChips .= '<rect x="' . $chipX . '" y="430" rx="20" ry="20" width="' . $width . '" height="38" fill="' . ($variant === 'social' ? 'rgba(255,255,255,0.16)' : 'rgba(255,255,255,0.78)') . '"/>';
            $pointChips .= '<text x="' . ($chipX + 16) . '" y="454" font-size="16" font-family="Arial, sans-serif" fill="' . ($variant === 'social' ? '#ffffff' : $ink) . '">' . $this->svgEscape($point) . '</text>';
            $chipX += $width + 12;
        }

        $imageBlock = '';
        if ($variant !== 'business' && trim((string) ($kit['hero_image_url'] ?? '')) !== '') {
            $imageBlock = '<image href="' . $this->svgEscape((string) $kit['hero_image_url']) . '" x="820" y="56" width="340" height="340" preserveAspectRatio="xMidYMid slice" clip-path="url(#heroClip)" opacity="0.96" />';
        }

        $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(220)->margin(1)->generate($url);
        $qrSvg = preg_replace('/<\?xml.*?\?>/s', '', (string) $qrSvg) ?? (string) $qrSvg;

        $background = $variant === 'social'
            ? '<rect width="1200" height="630" fill="' . $accent . '"/>'
            : '<defs><linearGradient id="promoGradient" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="' . $accentSoft . '"/><stop offset="100%" stop-color="#ffffff"/></linearGradient><clipPath id="heroClip"><rect x="820" y="56" width="340" height="340" rx="28" ry="28"/></clipPath></defs><rect width="1200" height="630" fill="url(#promoGradient)"/>';

        return '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630" role="img" aria-label="' . $this->svgEscape($headline) . '">'
            . $background
            . '<circle cx="1130" cy="560" r="150" fill="rgba(255,255,255,0.16)"/>'
            . $imageBlock
            . '<text x="72" y="56" font-size="15" letter-spacing="3" font-family="Arial, sans-serif" fill="' . ($variant === 'social' ? '#ffffff' : $ink) . '" opacity="0.78">' . $this->svgEscape(strtoupper($source . ' · PROMO ' . $promo)) . '</text>'
            . $headlineSvg
            . $bodySvg
            . $pointChips
            . '<rect x="72" y="504" rx="22" ry="22" width="620" height="92" fill="' . ($variant === 'social' ? 'rgba(255,255,255,0.16)' : 'rgba(255,255,255,0.84)') . '"/>'
            . '<text x="98" y="540" font-size="24" font-family="Arial, sans-serif" font-weight="700" fill="' . ($variant === 'social' ? '#ffffff' : $ink) . '">' . $this->svgEscape($cta) . '</text>'
            . '<text x="98" y="573" font-size="17" font-family="Arial, sans-serif" fill="' . ($variant === 'social' ? '#ffffff' : $ink) . '">' . $this->svgEscape($url) . '</text>'
            . '<g transform="translate(900,390)">' . $qrSvg . '</g>'
            . '</svg>';
    }

    public function stageOptions(): array
    {
        return ['all', 'identified', 'contacted', 'replied', 'qualified', 'proposal_sent', 'won', 'lost'];
    }

    public function channelOptions(?VerticalBlueprint $blueprint = null): array
    {
        $defaults = collect(is_array($blueprint?->default_channels_json) ? $blueprint->default_channels_json : [])
            ->map(fn ($value) => trim((string) $value))
            ->filter();
        $governed = collect(is_array($blueprint?->channel_playbook_json) ? $blueprint->channel_playbook_json : [])
            ->map(fn ($value) => trim((string) $value))
            ->filter();

        return array_values(array_unique(array_merge(
            ['all'],
            $governed->all(),
            $defaults->all(),
            ['facebook_groups', 'nextdoor', 'google_business', 'instagram', 'whatsapp', 'referral', 'flyer', 'website', 'manual']
        )));
    }

    private function channelPerformance(Collection $leads): array
    {
        $channels = [];

        foreach ($leads as $lead) {
            $channel = trim((string) $lead->lead_channel);
            if ($channel === '') {
                $channel = 'manual';
            }

            if (!isset($channels[$channel])) {
                $channels[$channel] = [
                    'channel' => $channel,
                    'channel_label' => $this->labelize($channel),
                    'leads' => 0,
                    'won' => 0,
                    'active' => 0,
                ];
            }

            $channels[$channel]['leads']++;
            if ((string) $lead->lead_stage === 'won') {
                $channels[$channel]['won']++;
            } elseif ((string) $lead->lead_stage !== 'lost') {
                $channels[$channel]['active']++;
            }
        }

        usort($channels, function (array $left, array $right): int {
            return [$right['won'], $right['active'], $right['leads']] <=> [$left['won'], $left['active'], $left['leads']];
        });

        return array_values($channels);
    }

    private function milestones(int $wonCount): array
    {
        $targets = [1, 10, 25, 50, 100];
        $items = [];

        foreach ($targets as $target) {
            $items[] = [
                'label' => 'First ' . $target,
                'target' => $target,
                'remaining' => max(0, $target - $wonCount),
                'completed' => $wonCount >= $target,
            ];
        }

        return $items;
    }

    private function topOffer(Collection $leads): ?array
    {
        $offers = [];

        foreach ($leads as $lead) {
            $offer = trim((string) $lead->offer_name);
            if ($offer === '') {
                continue;
            }

            if (!isset($offers[$offer])) {
                $offers[$offer] = [
                    'offer_name' => $offer,
                    'won' => 0,
                    'active' => 0,
                ];
            }

            if ((string) $lead->lead_stage === 'won') {
                $offers[$offer]['won']++;
            } elseif ((string) $lead->lead_stage !== 'lost') {
                $offers[$offer]['active']++;
            }
        }

        if (empty($offers)) {
            return null;
        }

        uasort($offers, fn (array $left, array $right) => [$right['won'], $right['active']] <=> [$left['won'], $left['active']]);

        return array_values($offers)[0] ?? null;
    }

    private function dailyPlan(
        Founder $founder,
        ?VerticalBlueprint $blueprint,
        ?FounderBusinessBrief $brief,
        ?FounderIcpProfile $icp,
        string $city,
        string $icpName,
        string $websiteStatus,
        string $websiteGenerationStatus,
        Collection $leads,
        Collection $followUpDue,
        ?array $bestChannel
    ): array {
        $companyName = trim((string) ($founder->company?->company_name ?? 'your business'));
        $defaultBlueprintChannels = collect(is_array($blueprint?->default_channels_json) ? $blueprint->default_channels_json : [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();
        $channelLabel = $bestChannel['channel_label'] ?? $this->labelize((string) ($defaultBlueprintChannels->first() ?: 'local outreach'));
        $cityLabel = $city !== '' ? $city : 'your local market';
        $icpLabel = $icpName !== '' ? $icpName : 'your ideal customers';

        $tasks = [];

        if ($websiteStatus !== 'live') {
            $tasks[] = [
                'title' => 'Publish your first website',
                'description' => $websiteGenerationStatus === 'ready_for_review'
                    ? 'Your draft is ready. Review it and publish so outreach has somewhere clear to send people.'
                    : 'Finish the autopilot draft so ' . $companyName . ' has a trust-building home before you push traffic.',
                'label' => 'Open Website',
                'href' => route('website'),
            ];
        }

        if ($leads->count() < 10) {
            $tasks[] = [
                'title' => 'List your first 10 prospects',
                'description' => 'Start with named people in ' . $cityLabel . ' who match ' . $icpLabel . ' and could buy your first offer.',
                'label' => 'Open First 100',
                'href' => route('founder.first-100'),
            ];
        }

        if ($followUpDue->count() > 0) {
            $tasks[] = [
                'title' => 'Follow up with due leads',
                'description' => $followUpDue->count() . ' leads are ready for a reply, check-in, or closing message today.',
                'label' => 'Open First 100',
                'href' => route('founder.first-100', ['stage' => 'all', 'q' => '']),
            ];
        }

        if ($leads->count() >= 10 && $followUpDue->count() === 0) {
            $tasks[] = [
                'title' => 'Work your best acquisition channel',
                'description' => 'Put today into ' . $channelLabel . ' and focus on getting one more real conversation moving.',
                'label' => 'Open First 100',
                'href' => route('founder.first-100'),
            ];
        }

        if ($leads->where('lead_stage', 'won')->count() === 0) {
            $tasks[] = [
                'title' => 'Push for your first paying customer',
                'description' => 'Keep the ask simple: one clear offer, one clear CTA, and one direct conversation with the closest-fit buyer.',
                'label' => 'Open Commerce',
                'href' => route('founder.commerce'),
            ];
        }

        if ($websiteStatus === 'live' && $leads->count() >= 5) {
            $tasks[] = [
                'title' => 'Drive traffic into your strongest page',
                'description' => 'Your site is live. Use today to push one vertical-specific offer angle and move people into a booking or purchase CTA.',
                'label' => 'Open Website',
                'href' => route('website'),
            ];
        }

        return [
            'headline' => 'Daily Revenue Plan',
            'focus' => 'Built around ' . $companyName . ', your vertical blueprint, and the First 100 customer path.',
            'city_label' => $cityLabel,
            'icp_label' => $icpLabel,
            'channel_label' => $channelLabel,
            'hook' => $this->leadAngle($blueprint, $brief, $icp),
            'tasks' => array_slice($tasks, 0, 4),
        ];
    }

    private function conversationEngine(
        Founder $founder,
        ?VerticalBlueprint $blueprint,
        ?FounderBusinessBrief $brief,
        ?FounderIcpProfile $icp,
        array $leadRows
    ): array {
        $activeConversations = collect($leadRows)->filter(fn (array $lead) => !in_array((string) ($lead['lead_stage'] ?? ''), ['won', 'lost'], true))->values();
        $due = $activeConversations->where('is_follow_up_due', true)->values();
        $priorityLead = $due->first() ?? $activeConversations->first();

        return [
            'headline' => 'Conversation Engine',
            'focus' => 'Use stage-aware scripts, objection handling, and closing prompts so every lead gets a clear next message.',
            'priority_lead_name' => $priorityLead['lead_name'] ?? '',
            'priority_lead_stage' => $priorityLead['lead_stage_label'] ?? '',
            'objection_prompts' => $this->objectionPrompts($blueprint, $brief, $icp),
        ];
    }

    private function followUpEngine(Founder $founder, Collection $leads): array
    {
        $activeRules = $founder->automationRules()
            ->where('status', 'active')
            ->get()
            ->keyBy(fn ($rule) => trim((string) ($rule->metadata_json['template_key'] ?? '')));

        $publicIntroLeads = $leads->filter(function (FounderLead $lead): bool {
            $meta = is_array($lead->meta_json) ? $lead->meta_json : [];
            $touches = collect(is_array($meta['touches'] ?? null) ? $meta['touches'] : []);

            return !empty($meta['public_intro'])
                && trim((string) $lead->lead_stage) === 'identified'
                && $touches->isEmpty();
        })->values();

        $dueLeads = $leads->filter(fn (FounderLead $lead) => $lead->next_follow_up_at && $lead->next_follow_up_at->lte(now()) && !in_array((string) $lead->lead_stage, ['won', 'lost'], true))->values();

        $templates = [
            [
                'template_key' => 'new-public-intro-lead-reminder',
                'name' => 'New public intro follow-up',
                'description' => 'Respond quickly when a QR, flyer, or promo visitor leaves their details on the public site.',
                'queue_count' => $publicIntroLeads->count(),
                'queue_label' => 'new intro leads',
                'active' => $activeRules->has('new-public-intro-lead-reminder'),
                'href' => route('founder.first-100', ['stage' => 'identified']),
            ],
            [
                'template_key' => 'lead-follow-up-due-reminder',
                'name' => 'Lead follow-up due',
                'description' => 'Keep open conversations moving before they go cold.',
                'queue_count' => $dueLeads->count(),
                'queue_label' => 'follow-ups due',
                'active' => $activeRules->has('lead-follow-up-due-reminder'),
                'href' => route('founder.first-100'),
            ],
        ];

        $queue = collect($leads)
            ->filter(fn (FounderLead $lead) => !in_array((string) $lead->lead_stage, ['won', 'lost'], true))
            ->sortBy([
                fn (FounderLead $lead) => $lead->next_follow_up_at ? 0 : 1,
                fn (FounderLead $lead) => optional($lead->next_follow_up_at)->timestamp ?? PHP_INT_MAX,
            ])
            ->take(5)
            ->map(function (FounderLead $lead): array {
                $meta = is_array($lead->meta_json) ? $lead->meta_json : [];

                return [
                    'lead_name' => (string) $lead->lead_name,
                    'stage_label' => $this->labelize((string) $lead->lead_stage),
                    'channel_label' => $this->labelize((string) $lead->lead_channel),
                    'next_follow_up_at' => optional($lead->next_follow_up_at)->toDayDateTimeString(),
                    'is_public_intro' => !empty($meta['public_intro']),
                    'promo_code' => trim((string) ($meta['promo_code'] ?? '')),
                ];
            })
            ->values()
            ->all();

        return [
            'headline' => 'Follow-Up Engine',
            'focus' => 'Turn captured leads into reliable next actions by saving founder follow-up rules and working the queue every day.',
            'templates' => $templates,
            'queue' => $queue,
            'active_rules_count' => collect($templates)->where('active', true)->count(),
        ];
    }

    private function acquisitionEngine(
        Founder $founder,
        ?VerticalBlueprint $blueprint,
        ?FounderBusinessBrief $brief,
        ?FounderIcpProfile $icp,
        Collection $leads,
        ?array $bestChannel
    ): array {
        $channels = collect($this->channelOptions($blueprint))
            ->reject(fn (string $value) => $value === 'all' || $value === 'manual')
            ->values();

        $priority = $bestChannel['channel'] ?? (string) $channels->first();
        $city = trim((string) ($brief?->location_city ?: $founder->company?->primary_city ?: 'your city'));
        $problem = trim((string) ($brief?->problem_solved ?: $founder->company?->company_brief ?: 'the problem you solve'));
        $icpName = trim((string) ($icp?->primary_icp_name ?: 'the right local customer'));
        $offer = trim((string) ($brief?->core_offer ?: $founder->company?->intelligence?->core_offer ?: 'your core offer'));
        $cta = trim((string) (($blueprint?->default_cta_json['primary'] ?? '') ?: 'Get started'));
        $leadCount = $leads->count();

        $existingChannels = $founder->leadChannels()->get()->keyBy('channel_key');

        $playbooks = $channels->take(4)->map(function (string $channel, int $index) use ($existingChannels, $priority, $blueprint, $city, $problem, $icpName, $offer, $cta, $leadCount): array {
            $script = $this->channelScript($channel, $blueprint?->code ?? '', $city, $problem, $icpName, $offer, $cta);
            $targetCount = $leadCount < 10 ? 10 : ($leadCount < 25 ? 5 : 3);
            /** @var FounderLeadChannel|null $storedChannel */
            $storedChannel = $existingChannels->get($channel);

            return [
                'channel' => $channel,
                'channel_label' => $this->labelize($channel),
                'priority' => strtolower($channel) === strtolower((string) $priority),
                'priority_rank' => $index + 1,
                'today_target' => $targetCount,
                'why_now' => $script['why_now'],
                'today_action' => $script['today_action'],
                'script_title' => $script['script_title'],
                'script_body' => $script['script_body'],
                'offer_angle' => $script['offer_angle'],
                'cta' => $script['cta'],
                'href' => $this->channelHref($channel),
                'lead_channel_id' => $storedChannel?->id,
                'adopted' => $storedChannel?->status === 'adopted',
                'status' => (string) ($storedChannel?->status ?? 'recommended'),
            ];
        })->all();

        return [
            'priority_channel' => $this->labelize((string) $priority),
            'lead_angle' => $this->leadAngle($blueprint, $brief, $icp),
            'playbooks' => $playbooks,
        ];
    }

    private function channelScript(
        string $channel,
        string $blueprintCode,
        string $city,
        string $problem,
        string $icpName,
        string $offer,
        string $cta
    ): array {
        $cityLabel = $city !== '' ? $city : 'your city';
        $problemLine = $problem !== '' ? $problem : 'this problem';
        $icpLine = $icpName !== '' ? $icpName : 'the right local customer';
        $offerLine = $offer !== '' ? $offer : 'our offer';

        return match (strtolower($channel)) {
            'facebook groups' => [
                'why_now' => 'Local trust-based buying decisions often start in neighborhood groups before they ever touch search.',
                'today_action' => 'Find 3 relevant groups in ' . $cityLabel . ' and write one helpful, non-salesy post that frames the problem first.',
                'script_title' => 'Helpful intro post',
                'script_body' => 'Hi everyone, I help ' . $icpLine . ' in ' . $cityLabel . ' who are dealing with ' . $problemLine . '. I just put together a simple ' . $offerLine . ' option for anyone who wants a clear next step. If this is useful, comment and I can send details.',
                'offer_angle' => 'Lead with a simple first step, not the full pitch.',
                'cta' => $cta,
            ],
            'nextdoor' => [
                'why_now' => 'Nextdoor is especially strong when the founder needs hyper-local trust and proof quickly.',
                'today_action' => 'Post one local introduction and one customer-result style update in your service area.',
                'script_title' => 'Neighborhood trust post',
                'script_body' => 'Neighbor intro: I run a local ' . $offerLine . ' business in ' . $cityLabel . '. I help ' . $icpLine . ' who want a more reliable way to solve ' . $problemLine . '. Happy to answer questions or share the offer details here.',
                'offer_angle' => 'Trust, reliability, and neighborhood convenience.',
                'cta' => $cta,
            ],
            'google business profile' => [
                'why_now' => 'Intent is already high here. The founder wins by tightening proof and posting fresh updates often.',
                'today_action' => 'Refresh your service description, add one proof point, and publish one update with a direct CTA.',
                'script_title' => 'Local offer update',
                'script_body' => 'Serving ' . $cityLabel . ': we help ' . $icpLine . ' solve ' . $problemLine . ' through ' . $offerLine . '. If you want the fastest next step, tap "' . $cta . '" and we will guide you from there.',
                'offer_angle' => 'High-intent searchers need clarity and proof more than creativity.',
                'cta' => $cta,
            ],
            'instagram' => [
                'why_now' => 'Instagram works best when the offer can be made visible and the founder keeps the CTA simple.',
                'today_action' => 'Post one outcome-focused proof post and one story with a clear call to message or book.',
                'script_title' => 'Proof-first caption',
                'script_body' => 'For ' . $icpLine . ' in ' . $cityLabel . ': if you are tired of ' . $problemLine . ', this is what ' . $offerLine . ' is designed to solve. Reply "info" and I will send the best starting option.',
                'offer_angle' => 'Show proof, then ask for the DM.',
                'cta' => $cta,
            ],
            'whatsapp referrals' => [
                'why_now' => 'Warm referrals shorten the trust cycle and can produce the first few customers fastest.',
                'today_action' => 'Send 5 direct messages to warm contacts asking for one introduction to someone who fits the ICP.',
                'script_title' => 'Referral ask',
                'script_body' => 'Quick favor: I just launched ' . $offerLine . ' for ' . $icpLine . ' in ' . $cityLabel . ' who need help with ' . $problemLine . '. Do you know one person I should speak to this week?',
                'offer_angle' => 'Ask for one intro, not broad help.',
                'cta' => $cta,
            ],
            'referrals', 'neighborhood referrals' => [
                'why_now' => 'Referral channels are often the fastest converter for local businesses once the founder has a clear first offer.',
                'today_action' => 'Message recent contacts or early customers and ask for one referral this week.',
                'script_title' => 'Simple referral message',
                'script_body' => 'I am growing ' . $offerLine . ' in ' . $cityLabel . ' for ' . $icpLine . '. If someone comes to mind who is dealing with ' . $problemLine . ', I would love one intro.',
                'offer_angle' => 'One intro, one clear offer, one simple ask.',
                'cta' => $cta,
            ],
            'apartment communities' => [
                'why_now' => 'This channel is strong when the business solves a repeat household problem and the founder needs concentrated local reach.',
                'today_action' => 'Contact 3 building managers or community admins with a clear resident offer and one proof point.',
                'script_title' => 'Community outreach message',
                'script_body' => 'Hi, I run a local ' . $offerLine . ' business serving ' . $cityLabel . '. We help residents who want a better solution for ' . $problemLine . '. I would love to share a simple resident offer if that is useful for your community.',
                'offer_angle' => 'Resident convenience + clear entry offer.',
                'cta' => $cta,
            ],
            'pinterest', 'niche communities', 'community groups', 'local community groups' => [
                'why_now' => 'These channels work when the founder can match the message to an already focused interest group.',
                'today_action' => 'Join 3 niche communities and share one educational or proof-driven post instead of a hard sell.',
                'script_title' => 'Education-first message',
                'script_body' => 'A quick idea for anyone in ' . $cityLabel . ' dealing with ' . $problemLine . ': we built ' . $offerLine . ' for ' . $icpLine . ' who want a simpler way to move forward. Happy to share the details if useful.',
                'offer_angle' => 'Teach first, pitch second.',
                'cta' => $cta,
            ],
            default => [
                'why_now' => 'This channel can create early traction when the founder uses one clear problem, one clear offer, and one clear next step.',
                'today_action' => 'Reach out to ' . $icpLine . ' in ' . $cityLabel . ' and test one concise message tied to ' . $problemLine . '.',
                'script_title' => 'Direct outreach message',
                'script_body' => 'Hi, I run ' . $offerLine . ' in ' . $cityLabel . ' for ' . $icpLine . ' who want help with ' . $problemLine . '. If it would help, I can share the simplest way to get started.',
                'offer_angle' => 'Keep the first step obvious.',
                'cta' => $cta,
            ],
        };
    }

    private function conversationPack(
        FounderLead $lead,
        Founder $founder,
        ?VerticalBlueprint $blueprint,
        ?FounderBusinessBrief $brief,
        ?FounderIcpProfile $icp
    ): array {
        $city = trim((string) ($brief?->location_city ?: $founder->company?->primary_city ?: 'your city'));
        $problem = trim((string) ($brief?->problem_solved ?: $founder->company?->company_brief ?: 'the problem you solve'));
        $icpName = trim((string) ($icp?->primary_icp_name ?: 'your ideal customer'));
        $offer = trim((string) ($lead->offer_name ?: $brief?->core_offer ?: $founder->company?->intelligence?->core_offer ?: 'your offer'));
        $cta = trim((string) (($blueprint?->default_cta_json['primary'] ?? '') ?: 'Get started'));
        $leadName = trim((string) $lead->lead_name);
        $channel = trim((string) $lead->lead_channel);
        $stage = trim((string) $lead->lead_stage);
        $touches = collect(is_array($lead->meta_json['touches'] ?? null) ? $lead->meta_json['touches'] : [])->reverse()->values();

        return [
            'recommended_message_type' => $this->recommendedMessageType($stage),
            'next_step_label' => $this->nextStepLabel($stage),
            'primary_script' => $this->stageScript('primary', $stage, $channel, $leadName, $city, $problem, $icpName, $offer, $cta),
            'follow_up_script' => $this->stageScript('follow_up', $stage, $channel, $leadName, $city, $problem, $icpName, $offer, $cta),
            'closing_script' => $this->stageScript('closing', $stage, $channel, $leadName, $city, $problem, $icpName, $offer, $cta),
            'objection_replies' => $this->objectionReplies($blueprint, $brief, $icp, $leadName, $offer, $cta),
            'touch_timeline' => $touches->take(3)->map(fn (array $touch): array => [
                'type' => $this->labelize((string) ($touch['type'] ?? 'touch')),
                'channel' => $this->labelize((string) ($touch['channel'] ?? 'manual')),
                'note' => (string) ($touch['note'] ?? ''),
                'logged_at' => Carbon::parse((string) ($touch['logged_at'] ?? now()->toIso8601String()))->toDayDateTimeString(),
            ])->all(),
        ];
    }

    private function recommendedMessageType(string $stage): string
    {
        return match ($stage) {
            'identified' => 'Initial outreach',
            'contacted' => 'Follow-up message',
            'replied' => 'Qualification reply',
            'qualified' => 'Offer framing',
            'proposal_sent' => 'Closing nudge',
            'won' => 'Customer welcome',
            'lost' => 'Reactivation later',
            default => 'Next-step message',
        };
    }

    private function nextStepLabel(string $stage): string
    {
        return match ($stage) {
            'identified' => 'Send first message',
            'contacted' => 'Follow up now',
            'replied' => 'Answer and qualify',
            'qualified' => 'Move to offer',
            'proposal_sent' => 'Ask for the decision',
            'won' => 'Welcome the customer',
            'lost' => 'Archive for now',
            default => 'Keep moving the conversation',
        };
    }

    private function stageScript(
        string $mode,
        string $stage,
        string $channel,
        string $leadName,
        string $city,
        string $problem,
        string $icpName,
        string $offer,
        string $cta
    ): string {
        $name = $leadName !== '' ? $leadName : 'there';
        $cityLine = $city !== '' ? $city : 'your area';
        $problemLine = $problem !== '' ? $problem : 'this problem';
        $offerLine = $offer !== '' ? $offer : 'our offer';
        $channelLine = $this->labelize($channel);

        $base = match ($stage) {
            'identified' => [
                'primary' => "Hi {$name}, I noticed you might be a fit for {$offerLine} in {$cityLine}. We help people who want a simpler way to handle {$problemLine}. If useful, I can send the quickest way to get started.",
                'follow_up' => "Hi {$name}, just bumping this in case {$problemLine} is still something you want solved. I can send the simplest first step for {$offerLine}.",
                'closing' => "If now is the right time, I can send the exact next step for {$offerLine}.",
            ],
            'contacted' => [
                'primary' => "Hi {$name}, checking back in on my last message about {$offerLine}. Most people I help in {$cityLine} want clarity on the first step before they commit. Happy to send that over.",
                'follow_up' => "Quick follow-up, {$name}: if {$problemLine} is still active for you, I can show you the easiest starting option.",
                'closing' => "If it helps, I can send one clear recommendation instead of all the options.",
            ],
            'replied' => [
                'primary' => "Thanks for replying, {$name}. Based on what you shared, the best fit is usually {$offerLine}. The goal is to solve {$problemLine} without overcomplicating the decision.",
                'follow_up' => "From what you said, I’d keep this simple and start with {$offerLine}. Want me to outline the next step?",
                'closing' => "If you want, we can move straight to the best starting option and get this moving.",
            ],
            'qualified' => [
                'primary' => "{$name}, you sound like a strong fit for {$offerLine}. It is designed for people in {$cityLine} who want a more reliable way to solve {$problemLine}.",
                'follow_up' => "Happy to answer anything that would help you decide whether {$offerLine} is the right move.",
                'closing' => "If the fit feels right, the next step is simply to {$cta}.",
            ],
            'proposal_sent' => [
                'primary' => "Hi {$name}, just checking in on the offer I sent over. Usually the fastest way forward is to choose the best starting option and get it scheduled.",
                'follow_up' => "Wanted to make sure you saw the proposal for {$offerLine}. I can answer any last question if that helps you decide.",
                'closing' => "If you are ready, we can lock this in now and move to the next step.",
            ],
            'won' => [
                'primary' => "Welcome, {$name}. Glad to have you in. The next step is simple, and I’ll keep the process clear from here.",
                'follow_up' => "Just making sure you have everything you need as we get started.",
                'closing' => "You’re all set. I’ll guide the next step from here.",
            ],
            default => [
                'primary' => "Hi {$name}, following up through {$channelLine} about {$offerLine}. If {$problemLine} is still relevant, I can help you with the next step.",
                'follow_up' => "Checking in again in case this is still on your radar.",
                'closing' => "If now is the right time, I can help you move forward today.",
            ],
        };

        return $base[$mode] ?? $base['primary'];
    }

    private function objectionPrompts(?VerticalBlueprint $blueprint, ?FounderBusinessBrief $brief, ?FounderIcpProfile $icp): array
    {
        $offer = trim((string) ($brief?->core_offer ?? 'your offer'));
        $objections = collect(is_array($icp?->objections_json) ? $icp->objections_json : [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();

        if ($objections->isEmpty()) {
            $objections = collect([
                'It feels too expensive right now',
                'I need to think about it',
                'I am not sure this is the right fit',
            ]);
        }

        return $objections->take(3)->map(fn (string $objection): array => [
            'objection' => $objection,
            'response' => 'A calm reply for "' . $objection . '": bring the conversation back to the cost of leaving the problem unsolved, simplify the offer, and restate the clearest next step for ' . $offer . '.',
        ])->all();
    }

    private function objectionReplies(
        ?VerticalBlueprint $blueprint,
        ?FounderBusinessBrief $brief,
        ?FounderIcpProfile $icp,
        string $leadName,
        string $offer,
        string $cta
    ): array {
        $name = $leadName !== '' ? $leadName : 'there';
        $offerLine = $offer !== '' ? $offer : 'this offer';
        $ctaLine = $cta !== '' ? $cta : 'get started';
        $objections = collect(is_array($icp?->objections_json) ? $icp->objections_json : [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();

        if ($objections->isEmpty()) {
            $objections = collect([
                'It is too expensive',
                'I need to think about it',
                'I am not ready yet',
            ]);
        }

        return $objections->take(3)->map(function (string $objection) use ($name, $offerLine, $ctaLine): array {
            $reply = match (strtolower($objection)) {
                'it is too expensive', 'too expensive' => "Totally fair, {$name}. Usually the real question is whether solving this problem now saves you more time, stress, or missed opportunities than waiting. If it helps, we can start with the simplest version of {$offerLine}.",
                'i need to think about it' => "Of course, {$name}. Most people just need clarity on the best next step. If it helps, I can narrow this down to the one option I’d recommend first so the decision feels simpler.",
                'i am not ready yet', 'not ready yet' => "That makes sense, {$name}. Would it help if I shared the easiest first step, so when the timing is right you already know exactly how to move?",
                default => "That makes sense, {$name}. Let’s simplify it: the goal of {$offerLine} is to make the next step clearer, not heavier. If it helps, I can point you to the easiest way to {$ctaLine}.",
            };

            return [
                'objection' => $objection,
                'reply' => $reply,
            ];
        })->all();
    }

    private function roundOfferPrice(float $value, string $businessModel): float
    {
        if ($businessModel === 'product') {
            return (float) (round($value / 5) * 5);
        }

        return (float) (round($value / 5) * 5);
    }

    private function leadAngle(?VerticalBlueprint $blueprint, ?FounderBusinessBrief $brief, ?FounderIcpProfile $icp): string
    {
        $problem = trim((string) ($brief?->problem_solved ?? ''));
        $icpName = trim((string) ($icp?->primary_icp_name ?? ''));
        $blueprintName = trim((string) ($blueprint?->name ?? 'your business'));

        if ($problem !== '' && $icpName !== '') {
            return 'Lead with the problem "' . $problem . '" and position ' . $blueprintName . ' as the clear first step for ' . $icpName . '.';
        }

        return 'Lead with a direct-response hook: name the problem, show the easy first step, and end with one obvious CTA.';
    }

    private function channelHref(string $channel): string
    {
        return match (strtolower($channel)) {
            'google business profile' => route('website'),
            default => route('founder.marketing'),
        };
    }

    private function labelize(string $value): string
    {
        $clean = trim(str_replace(['_', '-'], ' ', $value));

        return $clean === '' ? 'Unknown' : ucwords($clean);
    }

    private function offlineBridge(
        Founder $founder,
        ?VerticalBlueprint $blueprint,
        ?FounderBusinessBrief $brief,
        ?FounderIcpProfile $icp
    ): array {
        $company = $founder->company;
        $path = trim((string) ($company?->website_path ?? ''), '/');
        $baseUrl = 'https://app.hatchers.ai/' . ($path !== '' ? $path : 'your-business');
        $allLeads = $founder->leads()->get();
        $links = $founder->promoLinks()->latest()->limit(8)->get()->map(function (FounderPromoLink $link) use ($baseUrl): array {
            $query = http_build_query(array_filter([
                'src' => $link->source_channel,
                'promo' => $link->promo_code,
                'offer' => $link->offer_title,
            ]));

            return [
                'id' => $link->id,
                'title' => (string) $link->title,
                'source_channel' => (string) $link->source_channel,
                'source_channel_label' => $this->labelize((string) $link->source_channel),
                'promo_code' => (string) $link->promo_code,
                'cta_label' => (string) ($link->cta_label ?? ''),
                'offer_title' => (string) ($link->offer_title ?? ''),
                'url' => $baseUrl . ($query !== '' ? '?' . $query : ''),
                'status' => (string) $link->status,
            ];
        })->map(function (array $link) use ($allLeads): array {
            $stats = $this->promoLinkStatsFromLeads($allLeads, (string) ($link['promo_code'] ?? ''), (string) ($link['source_channel'] ?? ''));
            $link['stats'] = $stats;

            return $link;
        })->all();

        $problem = trim((string) ($brief?->problem_solved ?? $company?->company_brief ?? 'the problem you solve'));
        $icpName = trim((string) ($icp?->primary_icp_name ?? 'your ideal local customer'));
        $offer = trim((string) ($brief?->core_offer ?? $company?->intelligence?->core_offer ?? 'your offer'));

        return [
            'headline' => 'Offline To Online Bridge',
            'focus' => 'Turn flyers, QR codes, referral cards, and neighborhood traffic into named leads inside Hatchers OS.',
            'quick_ideas' => [
                'Use one QR-ready link per source so the OS can tell you which offline channel actually creates leads.',
                'Lead with one simple problem and one simple CTA, not a full business explanation.',
                'Use the same promo code on the flyer and in the OS so you can trace the source later.',
            ],
            'promo_links' => $links,
            'starter_copy' => [
                'headline' => 'Need help with ' . $problem . '?',
                'body' => 'Scan to see the easiest way to get started with ' . $offer . ' for ' . $icpName . '.',
                'cta' => 'Scan to get started',
            ],
        ];
    }

    private function syncLeadChannels(Founder $founder, ?VerticalBlueprint $blueprint, array $playbooks): void
    {
        foreach ($playbooks as $playbook) {
            if (!is_array($playbook) || trim((string) ($playbook['channel'] ?? '')) === '') {
                continue;
            }

            $existing = FounderLeadChannel::query()
                ->where('founder_id', $founder->id)
                ->where('channel_key', (string) $playbook['channel'])
                ->first();

            $payload = [
                'company_id' => $founder->company?->id,
                'vertical_blueprint_id' => $blueprint?->id,
                'channel_label' => (string) ($playbook['channel_label'] ?? $this->labelize((string) $playbook['channel'])),
                'status' => $existing && $existing->status === 'adopted' ? 'adopted' : 'recommended',
                'priority_rank' => (int) ($playbook['priority_rank'] ?? 0),
                'daily_target' => (int) ($playbook['today_target'] ?? 0),
                'script_title' => (string) ($playbook['script_title'] ?? ''),
                'script_body' => (string) ($playbook['script_body'] ?? ''),
                'offer_angle' => (string) ($playbook['offer_angle'] ?? ''),
                'meta_json' => [
                    'why_now' => (string) ($playbook['why_now'] ?? ''),
                    'today_action' => (string) ($playbook['today_action'] ?? ''),
                    'cta' => (string) ($playbook['cta'] ?? ''),
                    'href' => (string) ($playbook['href'] ?? ''),
                ],
            ];

            if ($existing) {
                $existing->forceFill($payload)->save();
                continue;
            }

            FounderLeadChannel::query()->create(array_merge($payload, [
                'founder_id' => $founder->id,
                'channel_key' => (string) $playbook['channel'],
            ]));
        }
    }

    private function syncFirstHundredTracker(
        Founder $founder,
        ?VerticalBlueprint $blueprint,
        array $metrics,
        array $dailyPlan,
        array $acquisitionEngine,
        ?array $bestChannel
    ): void {
        FounderFirstHundredTracker::query()->updateOrCreate(
            [
                'founder_id' => $founder->id,
                'status' => 'active',
            ],
            [
                'company_id' => $founder->company?->id,
                'vertical_blueprint_id' => $blueprint?->id,
                'target_customers' => 100,
                'customers_won' => (int) ($metrics['customers_won'] ?? 0),
                'active_leads' => (int) ($metrics['active_conversations'] ?? 0),
                'follow_up_due' => (int) ($metrics['follow_up_due'] ?? 0),
                'best_channel' => (string) ($bestChannel['channel'] ?? ''),
                'progress_percent' => (int) ($metrics['first_hundred_progress_percent'] ?? 0),
                'daily_plan_json' => $dailyPlan,
                'acquisition_summary_json' => [
                    'priority_channel' => (string) ($acquisitionEngine['priority_channel'] ?? ''),
                    'lead_angle' => (string) ($acquisitionEngine['lead_angle'] ?? ''),
                    'playbooks' => array_values(array_map(function ($playbook): array {
                        return is_array($playbook)
                            ? [
                                'channel' => (string) ($playbook['channel'] ?? ''),
                                'channel_label' => (string) ($playbook['channel_label'] ?? ''),
                                'today_target' => (int) ($playbook['today_target'] ?? 0),
                                'priority' => (bool) ($playbook['priority'] ?? false),
                            ]
                            : [];
                    }, (array) ($acquisitionEngine['playbooks'] ?? []))),
                ],
                'last_synced_at' => now(),
            ]
        );
    }

    private function syncConversationThreads(Founder $founder, Collection $leads): void
    {
        foreach ($leads as $lead) {
            if (!$lead instanceof FounderLead) {
                continue;
            }

            $meta = is_array($lead->meta_json) ? $lead->meta_json : [];
            $touches = collect(is_array($meta['touches'] ?? null) ? $meta['touches'] : [])->filter(fn ($touch) => is_array($touch))->values();
            $latestTouch = $touches->last();
            $channel = trim((string) ($lead->lead_channel ?: 'manual'));
            $leadChannel = $founder->leadChannels()->where('channel_key', $channel)->first();

            FounderConversationThread::query()->updateOrCreate(
                [
                    'founder_id' => $founder->id,
                    'thread_key' => 'lead-' . $lead->id,
                ],
                [
                    'company_id' => $founder->company?->id,
                    'founder_lead_id' => $lead->id,
                    'founder_lead_channel_id' => $leadChannel?->id,
                    'source_channel' => $channel,
                    'status' => in_array((string) $lead->lead_stage, ['won', 'lost'], true) ? 'closed' : 'open',
                    'recommended_sequence_json' => $touches->map(function (array $touch): array {
                        return [
                            'type' => (string) ($touch['type'] ?? ''),
                            'channel' => (string) ($touch['channel'] ?? ''),
                            'note' => (string) ($touch['note'] ?? ''),
                            'logged_at' => (string) ($touch['logged_at'] ?? ''),
                        ];
                    })->all(),
                    'latest_message' => (string) ($latestTouch['note'] ?? $lead->stage_notes ?? ''),
                    'next_follow_up_at' => $lead->next_follow_up_at,
                    'last_activity_at' => $lead->last_followed_up_at ?? $lead->updated_at,
                    'meta_json' => [
                        'lead_stage' => (string) $lead->lead_stage,
                        'contact_handle' => (string) ($lead->contact_handle ?? ''),
                        'public_intro' => !empty($meta['public_intro']),
                    ],
                ]
            );
        }
    }

    private function syncPricingRecommendations(
        Founder $founder,
        ?VerticalBlueprint $blueprint,
        array $offers,
        string $currency,
        array $recommendations,
        array $meta
    ): array {
        $company = $founder->company;
        $businessModel = (string) ($company?->business_model ?? ($blueprint?->business_model ?? 'service'));
        $preferredPlatform = $businessModel === 'product' ? 'bazaar' : 'servio';
        $defaultTarget = collect($offers)
            ->first(fn (array $offer) => (string) ($offer['engine'] ?? '') === $preferredPlatform)
            ?? ($offers[0] ?? null);

        $stored = [];
        foreach ($recommendations as $index => $recommendation) {
            $key = (string) ($recommendation['key'] ?? 'recommendation-' . ($index + 1));
            $existing = FounderPricingRecommendation::query()
                ->where('founder_id', $founder->id)
                ->where('recommendation_key', $key)
                ->first();

            $payload = [
                'company_id' => $company?->id,
                'vertical_blueprint_id' => $blueprint?->id,
                'founder_action_plan_id' => $existing?->founder_action_plan_id ?: ((int) ($defaultTarget['id'] ?? 0) ?: null),
                'positioning' => (string) ($recommendation['positioning'] ?? ''),
                'title' => (string) ($recommendation['title'] ?? ''),
                'description' => (string) ($recommendation['description'] ?? ''),
                'currency' => strtoupper(trim($currency)) !== '' ? strtoupper(trim($currency)) : 'USD',
                'price' => (float) ($recommendation['price'] ?? 0),
                'status' => (string) ($existing?->status ?: 'generated'),
                'apply_target' => (string) ($existing?->apply_target ?: ($defaultTarget['engine'] ?? $preferredPlatform)),
                'meta_json' => array_merge($meta, [
                    'business_model' => $businessModel,
                    'offer_candidates' => collect($offers)->map(fn (array $offer): array => [
                        'id' => (int) ($offer['id'] ?? 0),
                        'title' => (string) ($offer['title'] ?? ''),
                        'engine' => (string) ($offer['engine'] ?? ''),
                    ])->values()->all(),
                ]),
                'generated_at' => now(),
            ];

            if ($existing) {
                $existing->forceFill($payload)->save();
                $record = $existing;
            } else {
                $record = FounderPricingRecommendation::query()->create(array_merge($payload, [
                    'founder_id' => $founder->id,
                    'recommendation_key' => $key,
                ]));
            }

            $stored[] = [
                'id' => $record->id,
                'key' => $record->recommendation_key,
                'title' => $record->title,
                'price' => (float) $record->price,
                'positioning' => $record->positioning,
                'description' => (string) ($record->description ?? ''),
                'status' => (string) $record->status,
                'applied_at' => optional($record->applied_at)->toDateTimeString(),
                'target_action_plan_id' => $record->founder_action_plan_id,
                'target_engine' => (string) ($record->apply_target ?? ''),
            ];
        }

        return $stored;
    }

    private function promoLinkStats(Founder $founder, string $promoCode, string $sourceChannel): array
    {
        return $this->promoLinkStatsFromLeads($founder->leads()->get(), $promoCode, $sourceChannel);
    }

    private function promoLinkStatsFromLeads(Collection $leads, string $promoCode, string $sourceChannel): array
    {
        $promoCode = strtoupper(trim($promoCode));
        $sourceChannel = trim($sourceChannel);

        $matched = $leads->filter(function (FounderLead $lead) use ($promoCode, $sourceChannel): bool {
            $meta = is_array($lead->meta_json) ? $lead->meta_json : [];
            $leadPromo = strtoupper(trim((string) ($meta['promo_code'] ?? '')));
            $leadSource = trim((string) ($meta['source_channel'] ?? ''));

            return $leadPromo === $promoCode && $leadSource === $sourceChannel;
        })->values();

        return [
            'captured_leads' => $matched->count(),
            'won_leads' => $matched->where('lead_stage', 'won')->count(),
            'follow_up_due' => $matched->filter(fn (FounderLead $lead) => $lead->next_follow_up_at && $lead->next_follow_up_at->lte(now()) && !in_array((string) $lead->lead_stage, ['won', 'lost'], true))->count(),
        ];
    }

    private function promoAssetImageUrl(array $output): string
    {
        foreach ((array) ($output['sections'] ?? []) as $section) {
            if (!is_array($section)) {
                continue;
            }

            $asset = is_array($section['asset'] ?? null) ? $section['asset'] : [];
            $preview = trim((string) ($asset['preview_url'] ?? ''));
            if ($preview !== '') {
                return $preview;
            }
        }

        foreach ((array) ($output['atlas_handoff']['asset_slots'] ?? []) as $slot) {
            if (!is_array($slot)) {
                continue;
            }

            $preview = trim((string) ($slot['preview_url'] ?? ''));
            if ($preview !== '') {
                return $preview;
            }
        }

        return '';
    }

    private function svgEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function svgTextBlock(string $text, int $x, int $y, int $maxWidth, int $fontSize, int $weight = 400, string $color = '#181717'): string
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $line = '';
        $limit = max(12, (int) floor($maxWidth / max(10, ($fontSize * 0.55))));

        foreach ($words as $word) {
            $candidate = trim($line . ' ' . $word);
            if (strlen($candidate) > $limit && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        $output = '';
        foreach ($lines as $index => $lineText) {
            $output .= '<text x="' . $x . '" y="' . ($y + ($index * ($fontSize + 10))) . '" font-size="' . $fontSize . '" font-family="Arial, sans-serif" font-weight="' . $weight . '" fill="' . $this->svgEscape($color) . '">' . $this->svgEscape($lineText) . '</text>';
        }

        return $output;
    }
}
