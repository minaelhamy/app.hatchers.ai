<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyIntelligence;
use App\Models\Founder;
use App\Models\FounderActionPlan;
use App\Models\FounderBusinessBrief;
use App\Models\FounderIcpProfile;
use App\Models\FounderWebsiteGenerationRun;
use App\Models\VerticalBlueprint;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class WebsiteAutopilotService
{
    public function __construct(
        private WebsiteProvisioningService $websiteProvisioningService,
        private AtlasIntelligenceService $atlasIntelligenceService,
        private AtlasWorkspaceService $atlasWorkspaceService
    )
    {
    }

    public function latestDraft(?Company $company): ?array
    {
        if (!$company) {
            return null;
        }

        $company->loadMissing('founder.moduleSnapshots');
        $run = $company->websiteGenerationRuns()->latest('id')->first();
        if (!$run) {
            return null;
        }

        $output = is_array($run->output_json) ? $run->output_json : [];
        $atlasAssets = $this->atlasWebsiteAssets($company);
        if (!empty($atlasAssets)) {
            $output['atlas_handoff'] = is_array($output['atlas_handoff'] ?? null) ? $output['atlas_handoff'] : [];
            $output['atlas_handoff']['asset_slots'] = $atlasAssets;
        }
        $output['sections'] = $this->hydrateSectionsWithAssets(
            is_array($output['sections'] ?? null) ? $output['sections'] : [],
            $atlasAssets
        );

        if ($output !== (is_array($run->output_json) ? $run->output_json : [])) {
            $run->forceFill([
                'output_json' => $output,
            ])->save();
        }

        return [
            'id' => $run->id,
            'status' => (string) $run->status,
            'engine' => (string) ($run->engine ?? ''),
            'generated_at' => optional($run->generated_at ?? $run->updated_at)->toDateTimeString(),
            'title' => (string) ($output['website_title'] ?? ''),
            'theme_template' => (string) ($output['theme_template'] ?? ''),
            'website_path' => (string) ($output['website_path'] ?? ''),
            'hero' => is_array($output['hero'] ?? null) ? $output['hero'] : [],
            'sections' => is_array($output['sections'] ?? null) ? $output['sections'] : [],
            'starter_offer' => is_array($output['starter_offer'] ?? null) ? $output['starter_offer'] : [],
            'image_queries' => array_values(array_filter(array_map('strval', (array) ($output['image_queries'] ?? [])))),
            'sell_like_crazy' => is_array($output['sell_like_crazy'] ?? null) ? $output['sell_like_crazy'] : [],
            'funnel_blocks' => $this->normalizeFunnelBlocks($output),
            'launch_checklist' => array_values(array_filter(array_map('strval', (array) ($output['launch_checklist'] ?? [])))),
            'atlas_handoff' => is_array($output['atlas_handoff'] ?? null) ? $output['atlas_handoff'] : [],
            'engine_sync' => is_array($output['engine_sync'] ?? null) ? $output['engine_sync'] : [],
        ];
    }

    public function generate(Founder $founder): array
    {
        $founder->loadMissing([
            'company.verticalBlueprint',
            'company.intelligence',
            'company.websiteGenerationRuns',
            'businessBrief',
            'icpProfiles',
            'actionPlans',
        ]);

        $company = $founder->company;
        $intelligence = $company?->intelligence;
        $blueprint = $company?->verticalBlueprint ?: ($company ? $this->fallbackBlueprint($company) : null);
        $brief = $founder->businessBrief ?: $company?->businessBrief ?: ($company ? $this->fallbackBusinessBrief($founder, $company, $intelligence, $blueprint) : null);
        $icp = $founder->icpProfiles()->latest()->first() ?: $company?->icpProfiles()->latest()->first() ?: ($company ? $this->fallbackIcp($founder, $company, $intelligence, $blueprint) : null);

        if (!$company || !$blueprint || !$brief) {
            return [
                'ok' => false,
                'error' => 'The website generator needs a company, business brief, and blueprint before it can build the first site.',
            ];
        }

        $draft = $this->buildDraft($founder, $company, $blueprint, $brief, $icp);

        $result = DB::transaction(function () use ($founder, $company, $blueprint, $brief, $icp, $draft): array {
            $run = FounderWebsiteGenerationRun::create([
                'founder_id' => $founder->id,
                'company_id' => $company->id,
                'vertical_blueprint_id' => $blueprint->id,
                'engine' => (string) $draft['website_engine'],
                'status' => 'in_progress',
                'input_json' => [
                    'company_name' => (string) $company->company_name,
                    'vertical' => (string) $blueprint->code,
                    'business_summary' => (string) $brief->business_summary,
                    'problem_solved' => (string) $brief->problem_solved,
                    'primary_icp_name' => (string) ($icp?->primary_icp_name ?? ''),
                    'primary_city' => (string) ($company->primary_city ?? ''),
                ],
                'output_json' => $draft,
                'generated_at' => now(),
            ]);

            $this->syncDraftToAtlas($founder, $draft);
            $engineSync = $this->syncDraftToWebsiteEngine($founder, $draft);
            $starterSync = $this->syncStarterOffer($founder, $draft);
            $engineSyncOk = (bool) ($engineSync['ok'] ?? false);
            $starterSyncOk = (bool) ($starterSync['ok'] ?? false);
            $generationOk = $engineSyncOk && $starterSyncOk;
            $failureMessage = trim(implode(' ', array_filter([
                !$engineSyncOk ? (string) ($engineSync['message'] ?? 'The website engine could not be updated.') : null,
                !$starterSyncOk ? (string) ($starterSync['message'] ?? 'The starter offers could not be created.') : null,
            ])));

            $run->forceFill([
                'status' => $generationOk ? 'ready' : 'failed',
                'output_json' => array_merge($draft, [
                    'engine_sync' => $engineSync,
                    'starter_sync' => $starterSync,
                ]),
                'generated_at' => now(),
            ])->save();

            $company->forceFill([
                'business_model' => (string) $draft['website_mode'],
                'website_engine' => (string) $draft['website_engine'],
                'website_path' => (string) $draft['website_path'],
                'website_status' => $generationOk ? 'in_progress' : 'not_started',
                'website_generation_status' => $generationOk ? 'ready_for_review' : 'queued',
                'launch_stage' => $generationOk ? 'website_draft_ready' : 'company_intelligence_complete',
                'website_url' => 'https://app.hatchers.ai/' . ltrim((string) $draft['website_path'], '/'),
            ])->save();

            $this->updateCompanyIntelligence($company, $draft);
            if ($generationOk) {
                $this->upsertReviewTasks($founder, $draft);
            }

            return [
                'run' => $run,
                'ok' => $generationOk,
                'error' => $failureMessage,
            ];
        });

        return array_filter([
            'ok' => (bool) ($result['ok'] ?? false),
            'error' => (string) ($result['error'] ?? ''),
            'run' => $result['run'] ?? null,
            'draft' => $this->latestDraft($company),
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function regenerateDraftBlock(Founder $founder, string $block): array
    {
        $company = $founder->company;
        $run = $company?->websiteGenerationRuns()->latest('id')->first();
        if (!$company || !$run) {
            return [
                'ok' => false,
                'error' => 'There is no website draft yet for this founder.',
            ];
        }

        $output = is_array($run->output_json) ? $run->output_json : [];
        $blueprint = $company->verticalBlueprint;
        $brief = $founder->businessBrief;
        $icp = $founder->icpProfiles()->latest()->first();
        $companyName = (string) ($company->company_name ?: $founder->full_name);
        $city = trim((string) ($company->primary_city ?: $brief?->location_city));
        $problemSolved = trim((string) ($brief?->problem_solved ?: $company->company_brief));
        $icpName = trim((string) ($icp?->primary_icp_name ?? 'busy local customers'));
        $pricing = is_array($output['pricing'] ?? null) ? $output['pricing'] : $this->draftPricing($blueprint, (string) ($company->business_model ?? 'service'), $companyName);
        $starterTitle = (string) ($output['starter_offer']['title'] ?? ($pricing['anchor_offer'] ?? 'Core offer'));
        $primaryCta = (string) ($output['hero']['primary_cta'] ?? ($blueprint->default_cta_json['primary'] ?? 'Get started'));
        $secondaryCta = (string) ($output['hero']['secondary_cta'] ?? ($blueprint->default_cta_json['secondary'] ?? 'See how it works'));
        $proof = $this->stringList($icp?->desired_outcomes_json ?? []);
        $objections = $this->stringList($icp?->objections_json ?? []);

        switch ($block) {
            case 'hero':
                $output['hero']['headline'] = $this->heroHeadline($blueprint, $companyName, $problemSolved, $city);
                $output['hero']['subhead'] = $this->heroSubhead($companyName, $starterTitle, $icpName, $city);
                break;
            case 'cta':
                $output['hero']['primary_cta'] = $primaryCta;
                $output['hero']['secondary_cta'] = $secondaryCta;
                break;
            case 'offer_stack':
                $output['sell_like_crazy']['offer_stack'] = implode(' · ', array_filter([$starterTitle, $primaryCta, $secondaryCta]));
                $output['starter_offer']['description'] = $this->starterDescription($companyName, $starterTitle, $icpName, $city, $this->stringList($icp?->pain_points_json ?? []), $proof);
                break;
            case 'faq':
                $output['funnel_blocks']['faq'] = collect($objections !== [] ? $objections : ['Why choose us?', 'How fast can we start?', 'What makes this offer worth it?'])
                    ->map(fn (string $objection): array => [
                        'question' => $objection,
                        'answer' => $this->faqAnswer($objection, $companyName, $starterTitle, $problemSolved, $icpName, $city),
                    ])->values()->all();
                break;
            default:
                return [
                    'ok' => false,
                    'error' => 'That draft block is not supported yet.',
                ];
        }

        $output['funnel_blocks'] = $this->normalizeFunnelBlocks($output);
        $run->forceFill([
            'output_json' => $output,
        ])->save();

        return [
            'ok' => true,
            'draft' => $this->latestDraft($company),
        ];
    }

    private function buildDraft(
        Founder $founder,
        Company $company,
        VerticalBlueprint $blueprint,
        $brief,
        ?FounderIcpProfile $icp
    ): array {
        $websiteBuild = $this->websiteBuildConfig($brief);
        $websiteMode = $this->resolveWebsiteMode(
            strtolower(trim((string) ($company->business_model ?? $blueprint->business_model ?? 'service'))),
            (string) ($websiteBuild['primary_website_focus'] ?? 'auto')
        );
        $engine = $websiteMode === 'product' ? 'bazaar' : 'servio';
        $companyName = trim((string) ($brief->business_name ?: $company->company_name ?: $founder->full_name));
        $city = trim((string) ($company->primary_city ?: $brief->location_city));
        $problemSolved = trim((string) ($brief->problem_solved ?: $company->company_brief));
        $coreOffer = trim((string) ($brief->core_offer ?: ($blueprint->default_offer_json['core_offer'] ?? '')));
        $icpName = trim((string) ($icp?->primary_icp_name ?? 'busy local customers'));
        $painPoints = $this->stringList($icp?->pain_points_json ?? []);
        $outcomes = $this->stringList($icp?->desired_outcomes_json ?? []);
        $objections = $this->stringList($icp?->objections_json ?? []);
        $channels = $this->stringList($blueprint->default_channels_json ?? []);
        $catalogItems = $this->catalogItemsFromWebsiteBuild((string) ($websiteBuild['offer_items'] ?? ''), $websiteMode);
        $pages = $this->pagePlan($blueprint, (string) ($websiteBuild['must_include_pages'] ?? ''));
        $tasks = $this->stringList($blueprint->default_tasks_json ?? []);
        $imageQueries = $this->imageQueries($blueprint, $company, $icpName, (string) ($websiteBuild['image_preferences'] ?? ''));
        $pricing = $this->draftPricing($blueprint, $websiteMode, $companyName, $catalogItems);
        $primaryCta = trim((string) (($websiteBuild['primary_cta'] ?? '') !== '' ? $websiteBuild['primary_cta'] : ($blueprint->default_cta_json['primary'] ?? ($websiteMode === 'service' ? 'Book now' : 'Shop now'))));
        $secondaryCta = trim((string) ($blueprint->default_cta_json['secondary'] ?? 'See how it works'));
        $websitePath = trim((string) ($company->website_path ?: Str::slug($companyName)));
        $theme = $this->pickTheme($engine, (string) ($websiteBuild['image_preferences'] ?? ''), (string) ($company->intelligence?->visual_style ?? ''));
        $websiteGoal = trim((string) ($websiteBuild['website_goal'] ?? ''));
        $faqPoints = $this->textareaList((string) ($websiteBuild['faq_points'] ?? ''));
        $proofPoints = $this->textareaList((string) ($websiteBuild['proof_points'] ?? ''));

        $headline = $this->heroHeadline($blueprint, $companyName, $problemSolved, $city);
        $subhead = $this->heroSubhead($companyName, $coreOffer, $icpName, $city);
        $briefLine = $problemSolved !== ''
            ? $problemSolved
            : 'We help ' . $icpName . ' get a clearer, faster path to the right result.';

        $starterMode = $websiteMode === 'product' ? 'product' : 'service';
        $starterSeed = $catalogItems[0] ?? null;
        $starterTitle = trim((string) ($starterSeed['title'] ?? $pricing['anchor_offer'] ?? ($coreOffer !== '' ? $coreOffer : ($starterMode === 'service' ? 'Signature service' : 'Signature product'))));
        $starterDescription = trim((string) ($starterSeed['description'] ?? $this->starterDescription($companyName, $starterTitle, $icpName, $city, $painPoints, $outcomes)));
        $starterPrice = trim((string) ($starterSeed['price'] ?? ''));
        if ($starterPrice === '') {
            $starterPrice = trim((string) ($pricing['starting_price'] ?? '49'));
        }
        if ($starterPrice === '') {
            $starterPrice = '49';
        }
        $contactBlock = $this->contactBlock($websiteBuild, $city, (string) $brief->delivery_scope);

        return [
            'website_engine' => $engine,
            'website_mode' => $websiteMode,
            'website_title' => $companyName,
            'website_path' => $websitePath !== '' ? $websitePath : 'your-business',
            'theme_template' => $theme['id'],
            'theme_label' => $theme['label'],
            'hero' => [
                'eyebrow' => strtoupper($city !== '' ? $city . ' · ' . $blueprint->name : $blueprint->name),
                'headline' => $headline,
                'subhead' => $subhead,
                'brief' => $briefLine,
                'primary_cta' => $primaryCta,
                'secondary_cta' => $secondaryCta,
            ],
            'sections' => [
                [
                    'title' => 'Who this is for',
                    'body' => 'Built for ' . $icpName . ' who want a faster, more trustworthy way to buy.',
                    'bullets' => $painPoints !== [] ? $painPoints : ['Busy schedule', 'Need a trusted local provider', 'Want simple next steps'],
                ],
                [
                    'title' => 'What you get',
                    'body' => $companyName . ' leads with ' . ($coreOffer !== '' ? $coreOffer : $starterTitle) . ' and a direct response path shaped around quick conversion.',
                    'bullets' => $catalogItems !== []
                        ? array_map(fn (array $item): string => $item['title'] . ' · ' . $item['price'], array_slice($catalogItems, 0, 4))
                        : ($outcomes !== [] ? $outcomes : ['Fast booking or checkout', 'Clear pricing', 'Easy follow-up']),
                ],
                [
                    'title' => 'Why people say yes',
                    'body' => 'This site follows a Sell Like Crazy structure: clear problem, clear promise, clear offer, and a single action path.',
                    'bullets' => $proofPoints !== [] ? $proofPoints : $this->defaultProofBullets($companyName, $starterTitle, $outcomes, $city),
                ],
                [
                    'title' => 'About ' . $companyName,
                    'body' => trim((string) ($brief->founder_story ?: $brief->business_summary ?: $company->company_brief)),
                    'bullets' => $websiteGoal !== '' ? [$websiteGoal] : ['We can refine this story later as your business grows.'],
                ],
                [
                    'title' => 'Contact and next step',
                    'body' => 'The website should make the next action obvious and easy to complete.',
                    'bullets' => $contactBlock,
                ],
            ],
            'starter_offer' => [
                'mode' => $starterMode,
                'title' => $starterTitle,
                'description' => $starterDescription,
                'price' => $starterPrice,
            ],
            'catalog_items' => $catalogItems,
            'pricing' => $pricing,
            'sell_like_crazy' => [
                'core_promise' => $headline,
                'lead_angle' => 'Make the first conversion feel obvious for ' . $icpName . '.',
                'offer_stack' => implode(' · ', array_filter([$starterTitle, $primaryCta, $secondaryCta])),
            ],
            'funnel_blocks' => [
                'lead_magnet' => [
                    'title' => 'Quick-start guide',
                    'body' => 'Offer a simple first-value asset for ' . $icpName . ' before asking for the full decision.',
                ],
                'problem' => [
                    'title' => 'The real problem',
                    'body' => $problemSolved !== '' ? $problemSolved : 'Spell out the expensive, frustrating problem the customer already feels.',
                ],
                'proof' => [
                    'title' => 'Why this works',
                    'bullets' => $proofPoints !== [] ? $proofPoints : ($outcomes !== [] ? $outcomes : ['Clear process', 'Faster next step', 'Local trust']),
                ],
                'offer_stack' => [
                    'title' => 'What they get',
                    'bullets' => $catalogItems !== []
                        ? array_map(fn (array $item): string => $item['title'] . ' · ' . $item['price'], array_slice($catalogItems, 0, 4))
                        : array_values(array_filter([$starterTitle, $primaryCta, $secondaryCta])),
                ],
                'guarantee' => [
                    'title' => 'Risk reversal',
                    'body' => 'Reduce the fear of the first yes with a clear promise, fast response, and easy next step.',
                ],
                'urgency' => [
                    'title' => 'Why now',
                    'body' => 'Give the buyer a reason to act this week, not someday.',
                ],
                'faq' => collect($faqPoints !== [] ? $faqPoints : ($objections !== [] ? $objections : ['How does it work?', 'Is pricing clear?', 'What happens next?']))
                    ->map(fn (string $objection): array => [
                        'question' => $objection,
                        'answer' => $this->faqAnswer($objection, $companyName, $starterTitle, $problemSolved, $icpName, $city),
                    ])->values()->all(),
            ],
            'atlas_handoff' => [
                'status' => 'requested',
                'asset_slots' => $this->buildAssetSlots(
                    $companyName,
                    $city,
                    $pages,
                    $imageQueries,
                    $problemSolved,
                    $icpName
                ),
                'content_package' => [
                    'website_title' => $companyName,
                    'hero_headline' => $headline,
                    'hero_subhead' => $subhead,
                    'primary_cta' => $primaryCta,
                    'secondary_cta' => $secondaryCta,
                    'catalog_items' => $catalogItems,
                ],
            ],
            'launch_checklist' => array_values(array_filter([
                'Review the hero promise and CTA before publishing.',
                'Confirm the first offer pricing and add-ons.',
                $websiteGoal !== '' ? 'Website goal: ' . $websiteGoal . '.' : null,
                $channels !== [] ? 'Start with these channels: ' . implode(', ', array_slice($channels, 0, 3)) . '.' : null,
                $tasks !== [] ? 'First execution sprint: ' . implode(' | ', array_slice($tasks, 0, 3)) . '.' : null,
            ])),
            'image_queries' => $imageQueries,
            'page_plan' => $pages,
        ];
    }

    private function syncDraftToWebsiteEngine(Founder $founder, array &$draft): array
    {
        $founder->loadMissing('businessBrief', 'company');
        $brief = $founder->businessBrief;
        $websiteBuild = $brief ? $this->websiteBuildConfig($brief) : [];
        $mediaState = $this->resolvedWebsiteMediaState($founder, $draft);
        $draft['atlas_handoff'] = array_merge(
            is_array($draft['atlas_handoff'] ?? null) ? $draft['atlas_handoff'] : [],
            ['asset_slots' => $mediaState['asset_slots']]
        );
        $mediaAssets = $mediaState['media_assets'];

        if ($mediaAssets === []) {
            return [
                'ok' => false,
                'message' => 'Website media could not be prepared yet, so Hatchers stopped the build before publishing a placeholder site.',
                'public_url' => '',
                'media_assets_count' => 0,
            ];
        }

        $result = $this->websiteProvisioningService->applyWebsiteSetup($founder, [
            'website_engine' => $draft['website_engine'],
            'website_mode' => $draft['website_mode'],
            'website_title' => $draft['website_title'],
            'website_path' => $draft['website_path'],
            'theme_template' => $draft['theme_template'],
            'description' => $this->websiteDescription($draft),
            'meta_title' => $this->websiteMetaTitle($draft),
            'meta_description' => $this->websiteMetaDescription($draft),
            'contact_email' => (string) ($websiteBuild['contact_email'] ?? $founder->email ?? ''),
            'contact_phone' => (string) ($websiteBuild['contact_phone'] ?? $websiteBuild['contact_phone_number'] ?? $websiteBuild['contact_mobile'] ?? $founder->phone ?? ''),
            'business_address' => (string) ($websiteBuild['business_address'] ?? ''),
            'whatsapp_number' => (string) ($websiteBuild['whatsapp_number'] ?? ''),
            'about_content' => $this->aboutContent($draft),
            'faq_items' => $this->faqItems($draft),
            'social_links' => $this->socialLinks((string) ($websiteBuild['social_links'] ?? '')),
            'feature_items' => $this->featureItems($draft),
            'testimonials' => $this->testimonials($draft),
            'story_items' => $this->storyItems($draft),
            'story_title' => $this->storyTitle($draft),
            'story_subtitle' => $this->storySubtitle($draft),
            'story_description' => $this->storyDescription($draft),
            'media_assets' => $mediaAssets,
            'hero_headline' => (string) ($draft['hero']['headline'] ?? ''),
            'hero_subhead' => (string) ($draft['hero']['subhead'] ?? ''),
            'hero_brief' => (string) ($draft['hero']['brief'] ?? ''),
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['error'] ?? ''),
            'public_url' => (string) ($result['public_url'] ?? ''),
            'media_assets_count' => count($mediaAssets),
        ];
    }

    private function syncStarterOffer(Founder $founder, array $draft): array
    {
        $starterOffer = is_array($draft['starter_offer'] ?? null) ? $draft['starter_offer'] : [];
        $title = trim((string) ($starterOffer['title'] ?? ''));
        if ($title === '') {
            return ['ok' => false, 'message' => 'No starter offer title was generated.'];
        }

        $existing = $founder->actionPlans()
            ->where('platform', (string) $draft['website_engine'])
            ->where('title', $title)
            ->exists();

        if ($existing) {
            return ['ok' => true, 'message' => 'Starter offer already exists.'];
        }

        $result = $this->websiteProvisioningService->createStarterRecord($founder, [
            'website_engine' => $draft['website_engine'],
            'starter_mode' => $starterOffer['mode'] ?? 'service',
            'starter_title' => $title,
            'starter_description' => $starterOffer['description'] ?? '',
            'starter_price' => trim((string) ($starterOffer['price'] ?? '')) !== ''
                ? (string) $starterOffer['price']
                : '49',
            'media_assets' => $this->starterRecordMedia($draft, 0),
        ]);

        if (!($result['ok'] ?? false)) {
            return ['ok' => false, 'message' => (string) ($result['error'] ?? 'Starter record could not be created.')];
        }

        FounderActionPlan::create([
            'founder_id' => $founder->id,
            'title' => $title,
            'description' => trim(implode("\n", [
                'Type: ' . ($starterOffer['mode'] ?? 'service'),
                'Engine: ' . (string) $draft['website_engine'],
                'Price: ' . (trim((string) ($starterOffer['price'] ?? '')) !== '' ? (string) $starterOffer['price'] : '49'),
                '',
                (string) ($starterOffer['description'] ?? ''),
            ])),
            'platform' => (string) $draft['website_engine'],
            'priority' => 74,
            'status' => 'created',
            'cta_label' => 'Open Commerce',
            'cta_url' => route('founder.commerce'),
        ]);

        foreach (array_slice((array) ($draft['catalog_items'] ?? []), 1, 3) as $index => $item) {
            if (!is_array($item) || trim((string) ($item['title'] ?? '')) === '') {
                continue;
            }

            $this->websiteProvisioningService->createStarterRecord($founder, [
                'website_engine' => $draft['website_engine'],
                'starter_mode' => $starterOffer['mode'] ?? 'service',
                'starter_title' => (string) $item['title'],
                'starter_description' => (string) ($item['description'] ?? ''),
                'starter_price' => trim((string) ($item['price'] ?? '')) !== ''
                    ? (string) $item['price']
                    : (trim((string) ($starterOffer['price'] ?? '')) !== '' ? (string) $starterOffer['price'] : '49'),
                'media_assets' => $this->starterRecordMedia($draft, $index + 1),
            ]);
        }

        return ['ok' => true, 'message' => 'Starter offer created from the website autopilot draft.'];
    }

    private function updateCompanyIntelligence(Company $company, array $draft): void
    {
        CompanyIntelligence::updateOrCreate(
            ['company_id' => $company->id],
            [
                'company_id' => $company->id,
                'core_offer' => (string) ($draft['starter_offer']['title'] ?? ''),
                'pricing_notes' => trim('Starter price: ' . (string) ($draft['starter_offer']['price'] ?? '')),
                'visual_style' => trim('Theme ' . (string) ($draft['theme_label'] ?? '') . ' · Image direction: ' . implode(', ', array_slice((array) ($draft['image_queries'] ?? []), 0, 3))),
                'last_summary' => $this->compactDraftSummary($draft),
                'intelligence_updated_at' => now(),
            ]
        );
    }

    private function syncDraftToAtlas(Founder $founder, array $draft): void
    {
        $assetSlots = (array) ($draft['atlas_handoff']['asset_slots'] ?? []);
        $contentPackage = (array) ($draft['atlas_handoff']['content_package'] ?? []);

        $this->atlasIntelligenceService->syncFounderMutation($founder, [
            'role' => 'founder',
            'action' => 'website_autopilot_generated',
            'field' => 'website_autopilot',
            'value' => (string) ($draft['website_title'] ?? ''),
            'payload' => [
                'website_autopilot' => [
                    'engine' => (string) ($draft['website_engine'] ?? ''),
                    'mode' => (string) ($draft['website_mode'] ?? ''),
                    'path' => (string) ($draft['website_path'] ?? ''),
                    'theme_template' => (string) ($draft['theme_template'] ?? ''),
                    'image_queries' => (array) ($draft['image_queries'] ?? []),
                    'asset_slots' => $assetSlots,
                    'content_package' => $contentPackage,
                    'sell_like_crazy' => (array) ($draft['sell_like_crazy'] ?? []),
                    'sections' => (array) ($draft['sections'] ?? []),
                    'starter_offer' => (array) ($draft['starter_offer'] ?? []),
                    'catalog_items' => (array) ($draft['catalog_items'] ?? []),
                ],
            ],
            'sync_summary' => 'Hatchers OS generated a website autopilot draft and requested Atlas image/content handoff for the first site.',
        ]);
    }

    private function upsertReviewTasks(Founder $founder, array $draft): void
    {
        $tasks = [
            [
                'title' => 'Review your generated website draft',
                'description' => 'The OS drafted your first website using your business brief, ICP, and vertical blueprint.',
                'priority' => 96,
            ],
            [
                'title' => 'Approve your first offer and publish path',
                'description' => 'Confirm the first offer, payment path, and CTA before publishing the site.',
                'priority' => 92,
            ],
        ];

        foreach ($tasks as $task) {
            FounderActionPlan::updateOrCreate(
                [
                    'founder_id' => $founder->id,
                    'title' => $task['title'],
                    'platform' => 'os',
                ],
                [
                    'description' => $task['description'],
                    'priority' => $task['priority'],
                    'status' => 'pending',
                    'cta_label' => 'Open Website Workspace',
                    'cta_url' => route('website'),
                ]
            );
        }
    }

    private function compactDraftSummary(array $draft): string
    {
        return trim(implode("\n", array_filter([
            'Website draft ready for review.',
            'Title: ' . (string) ($draft['website_title'] ?? ''),
            'Theme: ' . (string) ($draft['theme_label'] ?? ''),
            'Engine: ' . strtoupper((string) ($draft['website_engine'] ?? '')),
            'Primary CTA: ' . (string) ($draft['hero']['primary_cta'] ?? ''),
            'Starter offer: ' . (string) ($draft['starter_offer']['title'] ?? ''),
        ])));
    }

    private function websiteMediaAssets(Founder $founder, array $draft): array
    {
        $resolved = $this->normalizeResolvedAssets((array) ($draft['atlas_handoff']['asset_slots'] ?? []));

        if ($resolved === []) {
            return [];
        }

        $byKey = collect($resolved)->keyBy(fn (array $asset): string => trim((string) ($asset['slot_key'] ?? '')));
        $hero = $byKey->get('hero') ?? ($resolved[0] ?? null);
        $features = $byKey->get('features') ?? ($resolved[1] ?? $hero);
        $proof = $byKey->get('proof') ?? ($resolved[2] ?? $features ?? $hero);
        $story = $byKey->get('story') ?? ($resolved[3] ?? $features ?? $hero);
        $faq = $byKey->get('faq') ?? ($resolved[4] ?? $proof ?? $hero);
        $action = $byKey->get('action') ?? ($resolved[5] ?? $proof ?? $hero);

        return array_values(array_filter([
            $this->slotMedia('hero', 'hero banner', $hero),
            $this->slotMedia('landing', 'landing banner', $hero),
            $this->slotMedia('faq', 'faq image', $faq),
            $this->slotMedia('story', 'story image', $story),
            $this->slotMedia('section_one', 'section one banner', $features),
            $this->slotMedia('section_two', 'section two banner', $proof),
            $this->slotMedia('section_three', 'section three banner', $action),
            $this->slotMedia('category', 'category image', $features ?? $hero),
            $this->slotMedia('service_primary', 'service primary image', $hero ?? $features),
            $this->slotMedia('service_detail', 'service detail image', $features ?? $proof ?? $hero),
            $this->slotMedia('service_support', 'service support image', $action ?? $story ?? $hero),
            $this->slotMedia('testimonial_primary', 'testimonial image', $proof ?? $story ?? $hero),
            $this->slotMedia('gallery_primary', 'gallery image one', $story ?? $hero),
            $this->slotMedia('gallery_secondary', 'gallery image two', $features ?? $hero),
            $this->slotMedia('gallery_tertiary', 'gallery image three', $action ?? $proof ?? $hero),
            $this->slotMedia('why_choose_item', 'why choose us image', $story ?? $features ?? $hero),
        ]));
    }

    private function resolvedWebsiteMediaState(Founder $founder, array $draft): array
    {
        $assetSlots = (array) ($draft['atlas_handoff']['asset_slots'] ?? []);
        $resolved = $this->normalizeResolvedAssets($assetSlots);

        for ($attempt = 0; $attempt < 4 && $resolved === []; $attempt++) {
            $atlasAssets = $this->atlasWorkspaceService->websiteAssets($founder);
            $assetSlots = is_array($atlasAssets['asset_slots'] ?? null) ? $atlasAssets['asset_slots'] : [];
            $resolved = $this->normalizeResolvedAssets($assetSlots);

            if ($resolved !== []) {
                break;
            }

            if ($attempt < 3) {
                usleep(750000);
            }
        }

        $draft['atlas_handoff'] = array_merge(
            is_array($draft['atlas_handoff'] ?? null) ? $draft['atlas_handoff'] : [],
            ['asset_slots' => $assetSlots]
        );

        return [
            'asset_slots' => $assetSlots,
            'media_assets' => $this->websiteMediaAssets($founder, $draft),
        ];
    }

    private function normalizeResolvedAssets(array $assets): array
    {
        return collect($assets)
            ->filter(fn ($asset): bool => is_array($asset))
            ->map(function (array $asset): ?array {
                $sourceUrl = trim((string) ($asset['asset_url'] ?? $asset['preview_url'] ?? ''));
                if ($sourceUrl === '') {
                    return null;
                }

                return [
                    'source_url' => $sourceUrl,
                    'preview_url' => trim((string) ($asset['preview_url'] ?? $sourceUrl)),
                    'alt_text' => trim((string) ($asset['alt_text'] ?? $asset['slot_label'] ?? 'Website image')),
                    'credit_name' => trim((string) ($asset['credit_name'] ?? '')),
                    'credit_url' => trim((string) ($asset['credit_url'] ?? '')),
                    'slot_key' => trim((string) ($asset['slot_key'] ?? '')),
                    'slot_label' => trim((string) ($asset['slot_label'] ?? '')),
                    'provider' => trim((string) ($asset['provider'] ?? 'pexels')),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function slotMedia(string $target, string $label, ?array $asset): ?array
    {
        if (!is_array($asset) || trim((string) ($asset['source_url'] ?? '')) === '') {
            return null;
        }

        return [
            'target' => $target,
            'label' => $label,
            'source_url' => trim((string) ($asset['source_url'] ?? '')),
            'preview_url' => trim((string) ($asset['preview_url'] ?? '')),
            'alt_text' => trim((string) ($asset['alt_text'] ?? $label)),
            'credit_name' => trim((string) ($asset['credit_name'] ?? '')),
            'credit_url' => trim((string) ($asset['credit_url'] ?? '')),
            'provider' => trim((string) ($asset['provider'] ?? 'pexels')),
        ];
    }

    private function pickTheme(string $engine, string $imagePreferences = '', string $visualStyle = ''): array
    {
        $themes = $this->websiteProvisioningService->availableThemes($engine);
        if ($themes === []) {
            return ['id' => '1', 'label' => 'Theme 1'];
        }

        $styleSignal = strtolower(trim($imagePreferences . ' ' . $visualStyle));
        $preferredIndex = str_contains($styleSignal, 'luxury') || str_contains($styleSignal, 'premium')
            ? 2
            : (str_contains($styleSignal, 'friendly') || str_contains($styleSignal, 'playful') || str_contains($styleSignal, 'warm') ? 1 : 0);
        $theme = $themes[$preferredIndex] ?? $themes[0] ?? null;

        if (is_array($theme) && !empty($theme['id'])) {
            return $theme;
        }

        return ['id' => '1', 'label' => 'Theme 1'];
    }

    private function imageQueries(VerticalBlueprint $blueprint, Company $company, string $icpName, string $imagePreferences = ''): array
    {
        $queries = collect((array) ($blueprint->default_image_queries_json ?? []))
            ->map(fn ($item) => trim((string) $item))
            ->filter();

        if ($company->primary_city) {
            $queries->push(trim((string) $company->primary_city . ' local business'));
        }

        if ($icpName !== '') {
            $queries->push($icpName . ' lifestyle');
        }

        foreach ($this->textareaList($imagePreferences) as $preference) {
            $queries->push($preference);
        }

        return $queries->unique()->take(5)->values()->all();
    }

    private function buildAssetSlots(
        string $companyName,
        string $city,
        array $pages,
        array $imageQueries,
        string $problemSolved,
        string $icpName
    ): array {
        $baseQuery = $imageQueries[0] ?? ($companyName . ' local business');
        $cityHint = $city !== '' ? $city . ' ' : '';
        $secondQuery = $imageQueries[1] ?? ($baseQuery . ' customer');
        $thirdQuery = $imageQueries[2] ?? ($baseQuery . ' team');
        $fourthQuery = $imageQueries[3] ?? ($baseQuery . ' consultation');
        $fifthQuery = $imageQueries[4] ?? ($baseQuery . ' detail');

        return [
            [
                'slot_key' => 'hero',
                'slot_label' => 'Hero Banner',
                'provider' => 'pexels',
                'query' => trim($cityHint . $baseQuery . ' storefront lifestyle'),
                'alt_text' => $companyName . ' hero image',
                'visual_brief' => trim('Use a strong hero image that instantly shows the business context, local trust, and the main outcome for ' . $icpName . '. Problem focus: ' . $problemSolved . '.'),
                'status' => 'requested',
            ],
            [
                'slot_key' => 'features',
                'slot_label' => 'Features Section',
                'provider' => 'pexels',
                'query' => trim($fifthQuery . ' professional detail'),
                'alt_text' => $companyName . ' features section image',
                'visual_brief' => 'Use a detailed image that supports the offer, process, or quality of the work.',
                'status' => 'requested',
            ],
            [
                'slot_key' => 'proof',
                'slot_label' => 'Social Proof',
                'provider' => 'pexels',
                'query' => trim($secondQuery . ' happy customer'),
                'alt_text' => $companyName . ' proof section image',
                'visual_brief' => 'Use a people-first image that feels like trust, satisfaction, or a real customer result.',
                'status' => 'requested',
            ],
            [
                'slot_key' => 'story',
                'slot_label' => 'Founder Story',
                'provider' => 'pexels',
                'query' => trim($thirdQuery . ' small business owner'),
                'alt_text' => $companyName . ' story section image',
                'visual_brief' => 'Use a warm, human image that supports the About, founder, or story section.',
                'status' => 'requested',
            ],
            [
                'slot_key' => 'faq',
                'slot_label' => 'FAQ Section',
                'provider' => 'pexels',
                'query' => trim($fourthQuery . ' helpful support'),
                'alt_text' => $companyName . ' FAQ image',
                'visual_brief' => 'Use a calm, supportive image that fits questions, guidance, or easy next steps.',
                'status' => 'requested',
            ],
            [
                'slot_key' => 'action',
                'slot_label' => 'Call To Action',
                'provider' => 'pexels',
                'query' => trim($baseQuery . ' booking checkout action'),
                'alt_text' => $companyName . ' action section image',
                'visual_brief' => 'Use an image that reinforces momentum, action, booking, checkout, or contact.',
                'status' => 'requested',
            ],
        ];
    }

    private function atlasWebsiteAssets(Company $company): array
    {
        $atlasSnapshot = $company->founder?->moduleSnapshots?->firstWhere('module', 'atlas');
        $payload = is_array($atlasSnapshot?->payload_json) ? $atlasSnapshot->payload_json : [];

        return collect((array) ($payload['website_autopilot']['asset_slots'] ?? []))
            ->filter(fn ($asset): bool => is_array($asset))
            ->values()
            ->all();
    }

    private function hydrateSectionsWithAssets(array $sections, array $assetSlots): array
    {
        return collect($sections)
            ->filter(fn ($section): bool => is_array($section))
            ->values()
            ->map(function (array $section, int $index) use ($assetSlots): array {
                $asset = is_array($assetSlots[$index] ?? null) ? $assetSlots[$index] : [];
                if ($asset === []) {
                    return $section;
                }

                $section['asset'] = [
                    'slot_label' => (string) ($asset['slot_label'] ?? ''),
                    'status' => (string) ($asset['status'] ?? ''),
                    'preview_url' => (string) ($asset['preview_url'] ?? ''),
                    'asset_url' => (string) ($asset['asset_url'] ?? ''),
                    'alt_text' => (string) ($asset['alt_text'] ?? ''),
                    'credit_name' => (string) ($asset['credit_name'] ?? ''),
                    'credit_url' => (string) ($asset['credit_url'] ?? ''),
                ];

                return $section;
            })
            ->all();
    }

    private function normalizeFunnelBlocks(array $output): array
    {
        $blocks = is_array($output['funnel_blocks'] ?? null) ? $output['funnel_blocks'] : [];

        return array_merge([
            'lead_magnet' => [],
            'problem' => [],
            'proof' => [],
            'offer_stack' => [],
            'guarantee' => [],
            'urgency' => [],
            'faq' => [],
        ], $blocks);
    }

    private function stringList(array $values): array
    {
        return collect($values)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function heroHeadline(VerticalBlueprint $blueprint, string $companyName, string $problemSolved, string $city): string
    {
        $cityPrefix = $city !== '' ? $city . ' ' : '';
        $problemSolved = trim($problemSolved);

        if ($problemSolved !== '') {
            return $cityPrefix . $companyName . ' helps you ' . Str::of($problemSolved)->lower()->trim(" .")->value() . '.';
        }

        return match ((string) $blueprint->code) {
            'dog-walking' => $cityPrefix . $companyName . ' keeps your dog active, happy, and looked after without the scheduling chaos.',
            'home-cleaning' => $cityPrefix . $companyName . ' gives busy homes a cleaner space without the back-and-forth.',
            'barber-services' => $cityPrefix . $companyName . ' makes booking your next sharp cut feel simple and immediate.',
            'tutoring-coaching' => $cityPrefix . $companyName . ' helps clients get expert guidance with a clear next step.',
            'handmade-products' => $cityPrefix . $companyName . ' brings handcrafted products to buyers with a simple offer and clear story.',
            default => $cityPrefix . $companyName . ' helps local customers move from interest to action faster.',
        };
    }

    private function heroSubhead(string $companyName, string $coreOffer, string $icpName, string $city): string
    {
        $locationTail = $city !== '' ? ' in ' . $city : '';
        $offerLine = $coreOffer !== '' ? $coreOffer : 'a clear first offer';

        return $companyName . ' now launches with ' . $offerLine . ' designed for ' . $icpName . $locationTail . ', using a direct-response structure inspired by Sell Like Crazy.';
    }

    private function draftPricing(VerticalBlueprint $blueprint, string $websiteMode, string $companyName, array $catalogItems = []): array
    {
        $tiers = $this->stringList(array_values((array) ($blueprint->default_pricing_json ?? [])));
        $seedItem = $catalogItems[0] ?? null;
        $startingPrice = match ((string) $blueprint->code) {
            'dog-walking' => '20',
            'home-cleaning' => '60',
            'barber-services' => '25',
            'tutoring-coaching' => '35',
            'handmade-products' => '45',
            default => $websiteMode === 'service' ? '35' : '49',
        };
        if (is_array($seedItem) && trim((string) ($seedItem['price'] ?? '')) !== '') {
            $startingPrice = trim((string) $seedItem['price']);
        }

        return [
            'starting_price' => $startingPrice,
            'anchor_offer' => (string) ($seedItem['title'] ?? ($tiers[0] ?? ($websiteMode === 'service' ? 'Signature service' : 'Featured product'))),
            'mid_offer' => $tiers[1] ?? '',
            'premium_offer' => $tiers[2] ?? '',
            'pricing_story' => $companyName . ' should lead with one easy first step, one stronger core package, and one premium upgrade.',
        ];
    }

    private function starterDescription(
        string $companyName,
        string $starterTitle,
        string $icpName,
        string $city,
        array $painPoints,
        array $outcomes
    ): string {
        $painPoint = $painPoints[0] ?? 'a messy, unclear buying process';
        $outcome = $outcomes[0] ?? 'a simple, trustworthy result';
        $location = $city !== '' ? ' in ' . $city : '';

        return $starterTitle . ' from ' . $companyName . ' is built for ' . $icpName . $location . ' who are tired of ' . $painPoint . ' and want ' . $outcome . '.';
    }

    private function websiteBuildConfig($brief): array
    {
        $constraints = is_array($brief?->constraints_json ?? null) ? $brief->constraints_json : [];
        return is_array($constraints['website_build'] ?? null) ? $constraints['website_build'] : [];
    }

    private function resolveWebsiteMode(string $businessModel, string $focus): string
    {
        $businessModel = in_array($businessModel, ['product', 'service', 'hybrid'], true) ? $businessModel : 'service';
        $focus = strtolower(trim($focus));

        if (in_array($focus, ['product', 'service'], true)) {
            return $focus;
        }

        return $businessModel === 'hybrid' ? 'service' : $businessModel;
    }

    private function catalogItemsFromWebsiteBuild(string $raw, string $websiteMode): array
    {
        $items = collect(preg_split("/\r\n|\n|\r/", trim($raw)) ?: [])
            ->map(function (string $line) {
                $parts = array_map('trim', explode('|', $line));
                return [
                    'title' => (string) ($parts[0] ?? ''),
                    'price' => (string) ($parts[1] ?? ''),
                    'description' => (string) ($parts[2] ?? ''),
                ];
            })
            ->filter(fn (array $item) => $item['title'] !== '')
            ->values()
            ->all();

        if ($items !== []) {
            return $items;
        }

        return [[
            'title' => $websiteMode === 'product' ? 'Featured product' : 'Signature service',
            'price' => '',
            'description' => '',
        ]];
    }

    private function pagePlan(VerticalBlueprint $blueprint, string $mustIncludePages): array
    {
        $defaultPages = $this->stringList($blueprint->default_pages_json ?? []);
        $requestedPages = $this->textareaList($mustIncludePages);

        return collect(array_merge($defaultPages, $requestedPages, ['about us', 'faq', 'contact']))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->unique()
            ->take(8)
            ->values()
            ->all();
    }

    private function textareaList(string $value): array
    {
        return collect(preg_split("/\r\n|\n|\r|,/", trim($value)) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function contactBlock(array $websiteBuild, string $city, string $deliveryScope): array
    {
        return array_values(array_filter([
            trim((string) ($websiteBuild['contact_email'] ?? '')) !== '' ? 'Email: ' . trim((string) $websiteBuild['contact_email']) : null,
            trim((string) ($websiteBuild['contact_phone'] ?? '')) !== '' ? 'Phone: ' . trim((string) $websiteBuild['contact_phone']) : null,
            trim((string) ($websiteBuild['whatsapp_number'] ?? '')) !== '' ? 'WhatsApp: ' . trim((string) $websiteBuild['whatsapp_number']) : null,
            trim((string) ($websiteBuild['business_address'] ?? '')) !== '' ? 'Address: ' . trim((string) $websiteBuild['business_address']) : null,
            trim((string) ($websiteBuild['business_hours'] ?? '')) !== '' ? 'Hours: ' . trim((string) $websiteBuild['business_hours']) : null,
            $city !== '' ? 'City: ' . $city : null,
            trim($deliveryScope) !== '' ? 'Coverage: ' . trim($deliveryScope) : null,
        ]));
    }

    private function websiteDescription(array $draft): string
    {
        $hero = (array) ($draft['hero'] ?? []);
        $sections = array_values(array_filter((array) ($draft['sections'] ?? []), fn ($item) => is_array($item)));
        $sectionBody = trim((string) (($sections[0]['body'] ?? '') ?: ($sections[1]['body'] ?? '')));

        return trim(implode(' ', array_filter([
            trim((string) ($hero['subhead'] ?? '')),
            trim((string) ($hero['brief'] ?? '')),
            $sectionBody,
        ])));
    }

    private function websiteMetaTitle(array $draft): string
    {
        $title = trim((string) ($draft['website_title'] ?? ''));
        $hero = (array) ($draft['hero'] ?? []);
        $eyebrow = trim((string) ($hero['eyebrow'] ?? ''));

        return trim($title . ($eyebrow !== '' ? ' | ' . $eyebrow : ''));
    }

    private function websiteMetaDescription(array $draft): string
    {
        return mb_substr($this->websiteDescription($draft), 0, 255);
    }

    private function aboutContent(array $draft): string
    {
        $hero = (array) ($draft['hero'] ?? []);
        $sections = array_values(array_filter((array) ($draft['sections'] ?? []), fn ($item) => is_array($item)));
        $chunks = [];

        if (trim((string) ($hero['headline'] ?? '')) !== '') {
            $chunks[] = '<h2>' . e((string) $hero['headline']) . '</h2>';
        }

        foreach ($sections as $section) {
            $title = trim((string) ($section['title'] ?? ''));
            $body = trim((string) ($section['body'] ?? ''));
            $bullets = array_values(array_filter((array) ($section['bullets'] ?? []), fn ($item) => trim((string) $item) !== ''));

            if ($title !== '') {
                $chunks[] = '<h3>' . e($title) . '</h3>';
            }
            if ($body !== '') {
                $chunks[] = '<p>' . e($body) . '</p>';
            }
            if ($bullets !== []) {
                $chunks[] = '<ul>' . implode('', array_map(fn (string $bullet): string => '<li>' . e($bullet) . '</li>', $bullets)) . '</ul>';
            }
        }

        return implode("\n", $chunks);
    }

    private function faqItems(array $draft): array
    {
        $faq = array_values(array_filter((array) ($draft['funnel_blocks']['faq'] ?? []), fn ($item) => is_array($item)));

        return array_values(array_filter(array_map(function (array $item): ?array {
            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));

            if ($question === '' || $answer === '') {
                return null;
            }

            return [
                'question' => $question,
                'answer' => $answer,
            ];
        }, $faq)));
    }

    private function socialLinks(string $raw): array
    {
        $links = [];

        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            [$network, $url] = array_pad(preg_split('/\s*[:|-]\s*/', $line, 2) ?: [], 2, '');
            $network = trim((string) $network);
            $url = trim((string) $url);

            if ($url === '' && filter_var($network, FILTER_VALIDATE_URL)) {
                $url = $network;
                $network = (string) (parse_url($url, PHP_URL_HOST) ?: 'website');
            }

            if ($url === '') {
                continue;
            }

            $links[] = [
                'network' => $network !== '' ? $network : 'website',
                'url' => $url,
            ];
        }

        return $links;
    }

    private function featureItems(array $draft): array
    {
        $sections = array_values(array_filter((array) ($draft['sections'] ?? []), fn ($item) => is_array($item)));
        $items = [];

        foreach (array_slice($sections, 0, 3) as $section) {
            $title = trim((string) ($section['title'] ?? ''));
            $body = trim((string) ($section['body'] ?? ''));
            if ($title === '' || $body === '') {
                continue;
            }

            $items[] = [
                'title' => $title,
                'description' => $body,
            ];
        }

        return $items;
    }

    private function testimonials(array $draft): array
    {
        $proof = array_values(array_filter((array) (($draft['funnel_blocks']['proof']['bullets'] ?? [])), fn ($item) => trim((string) $item) !== ''));
        $items = [];

        foreach (array_slice($proof, 0, 3) as $index => $bullet) {
            $items[] = [
                'name' => $this->testimonialName($index),
                'position' => 'Verified customer',
                'description' => $this->normalizeTestimonialLine((string) $bullet, $draft),
                'star' => 5,
            ];
        }

        if ($items === []) {
            foreach ($this->defaultProofBullets(
                (string) ($draft['website_title'] ?? 'This business'),
                (string) ($draft['starter_offer']['title'] ?? 'the main offer'),
                [],
                ''
            ) as $index => $bullet) {
                $items[] = [
                    'name' => $this->testimonialName($index),
                    'position' => 'Verified customer',
                    'description' => $bullet,
                    'star' => 5,
                ];
            }
        }

        return $items;
    }

    private function storyItems(array $draft): array
    {
        $sections = array_values(array_filter((array) ($draft['sections'] ?? []), fn ($item) => is_array($item)));
        $items = [];

        foreach ($sections as $section) {
            $title = trim((string) ($section['title'] ?? ''));
            $bullets = array_values(array_filter((array) ($section['bullets'] ?? []), fn ($item) => trim((string) $item) !== ''));
            foreach (array_slice($bullets, 0, 3) as $bullet) {
                if ($title === '') {
                    continue;
                }

                $items[] = [
                    'title' => $title,
                    'description' => trim((string) $bullet),
                ];
            }
            if ($items !== []) {
                break;
            }
        }

        return array_slice($items, 0, 3);
    }

    private function storyTitle(array $draft): string
    {
        $hero = (array) ($draft['hero'] ?? []);

        return trim((string) ($hero['headline'] ?? ''));
    }

    private function storySubtitle(array $draft): string
    {
        $hero = (array) ($draft['hero'] ?? []);

        return trim((string) ($hero['subhead'] ?? ''));
    }

    private function storyDescription(array $draft): string
    {
        $sections = array_values(array_filter((array) ($draft['sections'] ?? []), fn ($item) => is_array($item)));

        return trim((string) (($sections[0]['body'] ?? '') ?: ($sections[1]['body'] ?? '')));
    }

    private function starterRecordMedia(array $draft, int $offset): array
    {
        $media = array_values(array_filter((array) ($draft['media_assets'] ?? []), fn ($item) => is_array($item)));
        if ($media === []) {
            $resolved = $this->normalizeResolvedAssets((array) ($draft['atlas_handoff']['asset_slots'] ?? []));
            $byKey = collect($resolved)->keyBy(fn (array $asset): string => trim((string) ($asset['slot_key'] ?? '')));
            $hero = $byKey->get('hero') ?? ($resolved[0] ?? null);
            $features = $byKey->get('features') ?? ($resolved[1] ?? $hero);
            $proof = $byKey->get('proof') ?? ($resolved[2] ?? $features ?? $hero);
            $story = $byKey->get('story') ?? ($resolved[3] ?? $features ?? $hero);
            $action = $byKey->get('action') ?? ($resolved[5] ?? $proof ?? $hero);

            $media = array_values(array_filter([
                $this->slotMedia('category', 'category image', $features ?? $hero),
                $this->slotMedia('service_primary', 'service primary image', $hero ?? $features),
                $this->slotMedia('service_detail', 'service detail image', $features ?? $proof ?? $hero),
                $this->slotMedia('service_support', 'service support image', $action ?? $story ?? $hero),
            ]));
        }

        $byTarget = collect($media)->keyBy(fn (array $item): string => trim((string) ($item['target'] ?? '')));
        $serviceTargets = ['service_primary', 'service_detail', 'service_support', 'hero', 'section_one', 'section_two', 'section_three'];

        $serviceImages = collect($serviceTargets)
            ->map(fn (string $target) => $byTarget->get($target))
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['source_url'] ?? '')) !== '')
            ->values();

        $slice = $serviceImages->slice($offset, 3)->values();
        if ($slice->isEmpty()) {
            $slice = $serviceImages->take(3)->values();
        }

        $categoryAsset = $byTarget->get('category') ?? $byTarget->get('section_one') ?? $byTarget->get('hero');

        return array_values(array_filter([
            is_array($categoryAsset) && trim((string) ($categoryAsset['source_url'] ?? '')) !== '' ? [
                'target' => 'category',
                'source_url' => trim((string) ($categoryAsset['source_url'] ?? '')),
            ] : null,
            ...$slice->map(fn (array $asset): array => [
                'target' => 'service',
                'source_url' => trim((string) ($asset['source_url'] ?? '')),
            ])->all(),
        ]));
    }

    private function defaultProofBullets(string $companyName, string $starterTitle, array $outcomes, string $city): array
    {
        $cityPrefix = trim($city) !== '' ? trim($city) . ' ' : '';

        return array_slice(array_values(array_filter(array_merge(
            $outcomes,
            [
                'A clear ' . strtolower($starterTitle) . ' with no confusing next step.',
                'Fast replies and a simple booking path from the first visit.',
                $cityPrefix . 'local trust with an offer that feels straightforward and reliable.',
            ]
        ))), 0, 3);
    }

    private function faqAnswer(string $question, string $companyName, string $starterTitle, string $problemSolved, string $icpName, string $city): string
    {
        $question = Str::lower(trim($question));
        $offer = trim($starterTitle) !== '' ? $starterTitle : 'the first offer';
        $problemLine = trim($problemSolved) !== '' ? trim($problemSolved) : 'make the next step simple and clear';
        $cityClause = trim($city) !== '' ? ' in ' . trim($city) : '';

        return match (true) {
            str_contains($question, 'price'), str_contains($question, 'cost'), str_contains($question, 'high') =>
                $companyName . ' keeps the pricing around ' . strtolower($offer) . ' simple and visible, so buyers can decide without guesswork. The offer is meant to feel clear for ' . $icpName . ', not confusing or pressured.',
            str_contains($question, 'how'), str_contains($question, 'work'), str_contains($question, 'what happens next') =>
                'The process is designed to feel straightforward' . $cityClause . ': review the offer, choose the next step, and get a clear follow-up from ' . $companyName . '. Everything is built around helping customers ' . $problemLine . '.',
            str_contains($question, 'trust'), str_contains($question, 'safe'), str_contains($question, 'sure') =>
                $companyName . ' is positioned to feel trustworthy from the first visit. The site explains the offer clearly, shows proof, and makes the next step obvious before asking for a commitment.',
            default =>
                $companyName . ' uses a direct-response structure so visitors understand what ' . strtolower($offer) . ' is, who it is for, and the easiest next step. That removes hesitation and helps the right customer act with confidence.',
        };
    }

    private function testimonialName(int $index): string
    {
        return ['Local Client', 'Repeat Client', 'Busy Customer'][$index] ?? ('Client ' . ($index + 1));
    }

    private function normalizeTestimonialLine(string $line, array $draft): string
    {
        $line = trim($line);
        if ($line === '') {
            return 'The experience felt simple, clear, and easy to trust from the first step.';
        }

        foreach (['not sure', 'too expensive', 'high', 'think about it', 'hesitation', 'confusing'] as $signal) {
            if (str_contains(Str::lower($line), $signal)) {
                $offer = (string) ($draft['starter_offer']['title'] ?? 'the offer');
                return 'I understood exactly what ' . strtolower($offer) . ' included, and it felt easy to decide and move forward.';
            }
        }

        return $line;
    }

    private function fallbackBlueprint(Company $company): ?VerticalBlueprint
    {
        $businessModel = strtolower(trim((string) ($company->business_model ?? 'service')));

        return match ($businessModel) {
            'product' => new VerticalBlueprint([
                'code' => 'fallback-product',
                'name' => 'Product Business',
                'business_model' => 'product',
                'engine' => 'bazaar',
                'description' => 'Fallback product website blueprint.',
                'default_offer_json' => ['core_offer' => 'Products', 'upsells' => ['Bundle', 'Best seller', 'Limited offer']],
                'default_pricing_json' => ['tier_1' => 'Core product', 'tier_2' => 'Bundle', 'tier_3' => 'Premium option'],
                'default_pages_json' => ['hero', 'featured_collection', 'product_grid', 'about', 'faq', 'offer_cta'],
                'default_tasks_json' => ['Define the core product', 'Add pricing', 'Review the first website draft'],
                'default_channels_json' => ['Instagram', 'Facebook', 'WhatsApp', 'Referrals'],
                'default_cta_json' => ['primary' => 'Shop now', 'secondary' => 'See collection'],
                'default_image_queries_json' => ['product showcase', 'product flatlay', 'brand packaging'],
                'funnel_framework_json' => ['Problem', 'Offer', 'Proof', 'CTA', 'FAQ'],
            ]),
            'hybrid' => new VerticalBlueprint([
                'code' => 'fallback-hybrid',
                'name' => 'Hybrid Business',
                'business_model' => 'hybrid',
                'engine' => 'servio',
                'description' => 'Fallback hybrid website blueprint.',
                'default_offer_json' => ['core_offer' => 'Services and products', 'upsells' => ['Starter package', 'Bundle', 'Upgrade']],
                'default_pricing_json' => ['tier_1' => 'Starter offer', 'tier_2' => 'Core package', 'tier_3' => 'Premium package'],
                'default_pages_json' => ['hero', 'services', 'products', 'about', 'faq', 'cta'],
                'default_tasks_json' => ['Clarify the main offer', 'Add starter items', 'Review the website draft'],
                'default_channels_json' => ['Instagram', 'WhatsApp', 'Referrals', 'Local SEO'],
                'default_cta_json' => ['primary' => 'Get started', 'secondary' => 'See offers'],
                'default_image_queries_json' => ['small business service', 'customer experience', 'product and service brand'],
                'funnel_framework_json' => ['Problem', 'Offer', 'Proof', 'CTA', 'FAQ'],
            ]),
            default => new VerticalBlueprint([
                'code' => 'fallback-service',
                'name' => 'Service Business',
                'business_model' => 'service',
                'engine' => 'servio',
                'description' => 'Fallback service website blueprint.',
                'default_offer_json' => ['core_offer' => 'Services', 'upsells' => ['Package', 'Priority booking', 'Upgrade']],
                'default_pricing_json' => ['tier_1' => 'Starter service', 'tier_2' => 'Popular package', 'tier_3' => 'Premium package'],
                'default_pages_json' => ['hero', 'services', 'how_it_works', 'about', 'faq', 'booking_cta'],
                'default_tasks_json' => ['Define the service offer', 'Add proof points', 'Review the first website draft'],
                'default_channels_json' => ['WhatsApp', 'Referrals', 'Local SEO', 'Community groups'],
                'default_cta_json' => ['primary' => 'Book now', 'secondary' => 'See services'],
                'default_image_queries_json' => ['service business', 'customer consultation', 'local business team'],
                'funnel_framework_json' => ['Problem', 'Offer', 'Proof', 'CTA', 'FAQ'],
            ]),
        };
    }

    private function fallbackBusinessBrief(Founder $founder, Company $company, ?CompanyIntelligence $intelligence, ?VerticalBlueprint $blueprint): FounderBusinessBrief
    {
        return new FounderBusinessBrief([
            'founder_id' => $founder->id,
            'company_id' => $company->id,
            'vertical_blueprint_id' => $blueprint?->id,
            'business_name' => (string) ($company->company_name ?? $founder->full_name),
            'business_summary' => (string) ($company->company_brief ?? ''),
            'problem_solved' => (string) ($intelligence?->problem_solved ?? ''),
            'core_offer' => (string) ($intelligence?->core_offer ?? ''),
            'business_type_detail' => (string) ($blueprint?->name ?? ucfirst((string) ($company->business_model ?? 'Business'))),
            'location_city' => (string) ($company->primary_city ?? ''),
            'location_country' => '',
            'service_radius' => (string) ($company->service_radius ?? ''),
            'delivery_scope' => (string) ($company->service_radius ?? ''),
            'proof_points' => (string) ($intelligence?->differentiators ?? ''),
            'founder_story' => (string) ($company->company_brief ?? ''),
            'constraints_json' => [],
            'status' => 'captured',
        ]);
    }

    private function fallbackIcp(Founder $founder, Company $company, ?CompanyIntelligence $intelligence, ?VerticalBlueprint $blueprint): FounderIcpProfile
    {
        return new FounderIcpProfile([
            'founder_id' => $founder->id,
            'company_id' => $company->id,
            'primary_icp_name' => (string) ($intelligence?->primary_icp_name ?? 'Ideal customer'),
            'pain_points_json' => [],
            'desired_outcomes_json' => $this->stringListFromText((string) ($intelligence?->buying_triggers ?? '')),
            'buying_triggers_json' => $this->stringListFromText((string) ($intelligence?->buying_triggers ?? '')),
            'objections_json' => $this->stringListFromText((string) ($intelligence?->objections ?? '')),
            'price_sensitivity' => 'unknown',
            'primary_channels_json' => $blueprint?->default_channels_json ?? [],
            'local_area_focus_json' => array_values(array_filter([(string) ($company->primary_city ?? '')])),
            'language_style' => (string) ($intelligence?->brand_voice ?? ''),
        ]);
    }

    private function stringListFromText(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            preg_split('/[\r\n,]+/', $value) ?: []
        )));
    }
}
