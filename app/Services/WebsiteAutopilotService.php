<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyIntelligence;
use App\Models\Founder;
use App\Models\FounderActionPlan;
use App\Models\FounderIcpProfile;
use App\Models\FounderWebsiteGenerationRun;
use App\Models\VerticalBlueprint;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class WebsiteAutopilotService
{
    public function __construct(
        private WebsiteProvisioningService $websiteProvisioningService,
        private AtlasIntelligenceService $atlasIntelligenceService
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
        $blueprint = $company?->verticalBlueprint;
        $brief = $founder->businessBrief;
        $icp = $founder->icpProfiles()->latest()->first();

        if (!$company || !$blueprint || !$brief) {
            return [
                'ok' => false,
                'error' => 'The website generator needs a company, business brief, and blueprint before it can build the first site.',
            ];
        }

        $draft = $this->buildDraft($founder, $company, $blueprint, $brief, $icp);

        $run = DB::transaction(function () use ($founder, $company, $blueprint, $brief, $icp, $draft): FounderWebsiteGenerationRun {
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

            $engineSync = $this->syncDraftToWebsiteEngine($founder, $draft);
            $starterSync = $this->syncStarterOffer($founder, $draft);

            $run->forceFill([
                'status' => 'ready',
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
                'website_status' => 'in_progress',
                'website_generation_status' => 'ready_for_review',
                'launch_stage' => 'website_draft_ready',
                'website_url' => 'https://app.hatchers.ai/' . ltrim((string) $draft['website_path'], '/'),
            ])->save();

            $this->updateCompanyIntelligence($company, $draft);
            $this->upsertReviewTasks($founder, $draft);
            $this->syncDraftToAtlas($founder, $draft);

            return $run;
        });

        return [
            'ok' => true,
            'run' => $run,
            'draft' => $this->latestDraft($company),
        ];
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
                        'answer' => 'Hatchers shaped this answer around ' . ($problemSolved !== '' ? $problemSolved : 'your founder brief') . ' so the founder can remove hesitation and move the buyer to the next step.',
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
        $engine = strtolower(trim((string) ($blueprint->engine ?? $company->website_engine ?? 'servio')));
        $websiteMode = strtolower(trim((string) ($blueprint->business_model ?? $company->business_model ?? 'service')));
        $theme = $this->pickTheme($engine);
        $companyName = trim((string) ($brief->business_name ?: $company->company_name ?: $founder->full_name));
        $city = trim((string) ($company->primary_city ?: $brief->location_city));
        $problemSolved = trim((string) ($brief->problem_solved ?: $company->company_brief));
        $coreOffer = trim((string) ($brief->core_offer ?: ($blueprint->default_offer_json['core_offer'] ?? '')));
        $icpName = trim((string) ($icp?->primary_icp_name ?? 'busy local customers'));
        $painPoints = $this->stringList($icp?->pain_points_json ?? []);
        $outcomes = $this->stringList($icp?->desired_outcomes_json ?? []);
        $objections = $this->stringList($icp?->objections_json ?? []);
        $channels = $this->stringList($blueprint->default_channels_json ?? []);
        $pages = $this->stringList($blueprint->default_pages_json ?? []);
        $tasks = $this->stringList($blueprint->default_tasks_json ?? []);
        $imageQueries = $this->imageQueries($blueprint, $company, $icpName);
        $pricing = $this->draftPricing($blueprint, $websiteMode, $companyName);
        $primaryCta = trim((string) ($blueprint->default_cta_json['primary'] ?? ($websiteMode === 'service' ? 'Book now' : 'Shop now')));
        $secondaryCta = trim((string) ($blueprint->default_cta_json['secondary'] ?? 'See how it works'));
        $websitePath = trim((string) ($company->website_path ?: Str::slug($companyName)));

        $headline = $this->heroHeadline($blueprint, $companyName, $problemSolved, $city);
        $subhead = $this->heroSubhead($companyName, $coreOffer, $icpName, $city);
        $briefLine = $problemSolved !== ''
            ? $problemSolved
            : 'We help ' . $icpName . ' get a clearer, faster path to the right result.';

        $starterMode = $websiteMode === 'product' ? 'product' : 'service';
        $starterTitle = trim((string) ($pricing['anchor_offer'] ?? ($coreOffer !== '' ? $coreOffer : ($starterMode === 'service' ? 'Signature service' : 'Signature product'))));
        $starterDescription = $this->starterDescription($companyName, $starterTitle, $icpName, $city, $painPoints, $outcomes);

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
                    'bullets' => $outcomes !== [] ? $outcomes : ['Fast booking or checkout', 'Clear pricing', 'Easy follow-up'],
                ],
                [
                    'title' => 'Why people say yes',
                    'body' => 'This site follows a Sell Like Crazy structure: clear problem, clear promise, clear offer, and a single action path.',
                    'bullets' => $objections !== [] ? $objections : ['Transparent pricing', 'Local trust', 'Simple first step'],
                ],
            ],
            'starter_offer' => [
                'mode' => $starterMode,
                'title' => $starterTitle,
                'description' => $starterDescription,
                'price' => (string) ($pricing['starting_price'] ?? '49'),
            ],
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
                    'bullets' => $outcomes !== [] ? $outcomes : ['Clear process', 'Faster next step', 'Local trust'],
                ],
                'offer_stack' => [
                    'title' => 'What they get',
                    'bullets' => array_values(array_filter([$starterTitle, $primaryCta, $secondaryCta])),
                ],
                'guarantee' => [
                    'title' => 'Risk reversal',
                    'body' => 'Reduce the fear of the first yes with a clear promise, fast response, and easy next step.',
                ],
                'urgency' => [
                    'title' => 'Why now',
                    'body' => 'Give the buyer a reason to act this week, not someday.',
                ],
                'faq' => collect($objections !== [] ? $objections : ['How does it work?', 'Is pricing clear?', 'What happens next?'])
                    ->map(fn (string $objection): array => [
                        'question' => $objection,
                        'answer' => 'Answer this objection using the founder brief, ICP, and the simplest possible next step.',
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
                ],
            ],
            'launch_checklist' => array_values(array_filter([
                'Review the hero promise and CTA before publishing.',
                'Confirm the first offer pricing and add-ons.',
                $channels !== [] ? 'Start with these channels: ' . implode(', ', array_slice($channels, 0, 3)) . '.' : null,
                $tasks !== [] ? 'First execution sprint: ' . implode(' | ', array_slice($tasks, 0, 3)) . '.' : null,
            ])),
            'image_queries' => $imageQueries,
            'page_plan' => $pages,
        ];
    }

    private function syncDraftToWebsiteEngine(Founder $founder, array $draft): array
    {
        $result = $this->websiteProvisioningService->applyWebsiteSetup($founder, [
            'website_engine' => $draft['website_engine'],
            'website_mode' => $draft['website_mode'],
            'website_title' => $draft['website_title'],
            'website_path' => $draft['website_path'],
            'theme_template' => $draft['theme_template'],
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['error'] ?? ''),
            'public_url' => (string) ($result['public_url'] ?? ''),
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
            return ['ok' => true, 'message' => 'Starter offer already exists in founder workflow.'];
        }

        $result = $this->websiteProvisioningService->createStarterRecord($founder, [
            'website_engine' => $draft['website_engine'],
            'starter_mode' => $starterOffer['mode'] ?? 'service',
            'starter_title' => $title,
            'starter_description' => $starterOffer['description'] ?? '',
            'starter_price' => $starterOffer['price'] ?? '0',
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
                'Price: ' . (string) ($starterOffer['price'] ?? '0'),
                '',
                (string) ($starterOffer['description'] ?? ''),
            ])),
            'platform' => (string) $draft['website_engine'],
            'priority' => 74,
            'status' => 'created',
            'cta_label' => 'Open Commerce',
            'cta_url' => route('founder.commerce'),
        ]);

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

    private function pickTheme(string $engine): array
    {
        $theme = $this->websiteProvisioningService->availableThemes($engine)[0] ?? null;

        if (is_array($theme) && !empty($theme['id'])) {
            return $theme;
        }

        return ['id' => '1', 'label' => 'Theme 1'];
    }

    private function imageQueries(VerticalBlueprint $blueprint, Company $company, string $icpName): array
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
        $cityTail = $city !== '' ? ' in ' . $city : '';
        $pageKeys = array_slice($pages, 0, 4);
        if ($pageKeys === []) {
            $pageKeys = ['hero', 'offer', 'trust', 'cta'];
        }

        $slots = [];
        foreach ($pageKeys as $index => $pageKey) {
            $query = $imageQueries[$index] ?? $baseQuery;
            $slotLabel = str_replace('_', ' ', (string) $pageKey);

            $slots[] = [
                'slot_key' => (string) $pageKey,
                'slot_label' => Str::title($slotLabel),
                'provider' => 'pexels',
                'query' => $query,
                'alt_text' => $companyName . ' ' . strtolower($slotLabel) . $cityTail,
                'visual_brief' => trim('Use a local, trust-building image for the ' . $slotLabel . ' section. Show ' . $icpName . ' moving toward the promised outcome. Problem focus: ' . $problemSolved . '.'),
                'status' => 'requested',
            ];
        }

        return $slots;
    }

    private function atlasWebsiteAssets(Company $company): array
    {
        $atlasSnapshot = $company->founder?->moduleSnapshots?->firstWhere('module', 'atlas');
        $payload = is_array($atlasSnapshot?->payload_json) ? $atlasSnapshot->payload_json : [];

        return collect((array) ($payload['website_autopilot']['asset_slots'] ?? []))
            ->filter('is_array')
            ->values()
            ->all();
    }

    private function hydrateSectionsWithAssets(array $sections, array $assetSlots): array
    {
        return collect($sections)
            ->filter('is_array')
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

    private function draftPricing(VerticalBlueprint $blueprint, string $websiteMode, string $companyName): array
    {
        $tiers = $this->stringList(array_values((array) ($blueprint->default_pricing_json ?? [])));
        $startingPrice = match ((string) $blueprint->code) {
            'dog-walking' => '20',
            'home-cleaning' => '60',
            'barber-services' => '25',
            'tutoring-coaching' => '35',
            'handmade-products' => '45',
            default => $websiteMode === 'service' ? '35' : '49',
        };

        return [
            'starting_price' => $startingPrice,
            'anchor_offer' => $tiers[0] ?? ($websiteMode === 'service' ? 'Signature service' : 'Featured product'),
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
}
