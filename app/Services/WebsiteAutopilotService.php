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
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebsiteAutopilotService
{
    public function __construct(
        private WebsiteProvisioningService $websiteProvisioningService,
        private AtlasIntelligenceService $atlasIntelligenceService,
        private AtlasWorkspaceService $atlasWorkspaceService,
        private FounderModuleSyncService $founderModuleSyncService,
        private WebsiteAutopilotSchemaService $websiteAutopilotSchemaService,
        private WebsiteAutopilotMapperService $websiteAutopilotMapperService,
        private WebsiteAutopilotValidatorService $websiteAutopilotValidatorService,
        private WebsiteAutopilotQualityGateService $websiteAutopilotQualityGateService
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
            'theme_label' => (string) ($output['theme_label'] ?? ''),
            'theme_match_reasons' => array_values(array_filter(array_map('strval', (array) ($output['theme_match_reasons'] ?? [])))),
            'theme_candidates' => array_values(array_filter((array) ($output['theme_candidates'] ?? []), fn ($item) => is_array($item))),
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
            'quality_audit' => is_array($output['quality_audit'] ?? null) ? $output['quality_audit'] : [],
        ];
    }

    public function generate(Founder $founder): array
    {
        $this->logAutopilotStep('generate.start', $founder, [
            'founder_id' => $founder->id,
            'company_id' => $founder->company_id,
        ]);

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
        $this->logAutopilotStep('draft.built', $founder, [
            'engine' => (string) ($draft['website_engine'] ?? ''),
            'mode' => (string) ($draft['website_mode'] ?? ''),
            'path' => (string) ($draft['website_path'] ?? ''),
            'theme_template' => (string) ($draft['theme_template'] ?? ''),
            'catalog_count' => count((array) ($draft['catalog_items'] ?? [])),
            'faq_count' => count((array) ($draft['funnel_blocks']['faq'] ?? [])),
            'media_query_count' => count((array) ($draft['image_queries'] ?? [])),
        ]);

        $engineSyncBootstrap = $this->founderModuleSyncService->syncFounder($founder, (string) $draft['website_engine']);
        if (!($engineSyncBootstrap['ok'] ?? false)) {
            $this->logAutopilotStep('founder_sync.failed', $founder, [
                'engine' => (string) ($draft['website_engine'] ?? ''),
                'message' => (string) ($engineSyncBootstrap['message'] ?? ''),
            ], 'warning');

            return [
                'ok' => false,
                'error' => (string) ($engineSyncBootstrap['message'] ?? 'We could not provision the founder account in the website engine yet.'),
            ];
        }

        $this->logAutopilotStep('founder_sync.ok', $founder, [
            'engine' => (string) ($draft['website_engine'] ?? ''),
        ]);

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
            $blogSync = $this->syncStarterBlog($founder, $draft);
            $engineSyncOk = (bool) ($engineSync['ok'] ?? false);
            $starterSyncOk = (bool) ($starterSync['ok'] ?? false);
            $blogSyncOk = (bool) ($blogSync['ok'] ?? false);
            $generationOk = $engineSyncOk && $starterSyncOk && $blogSyncOk;
            $failureMessage = trim(implode(' ', array_filter([
                !$engineSyncOk ? (string) ($engineSync['message'] ?? 'The website engine could not be updated.') : null,
                !$starterSyncOk ? (string) ($starterSync['message'] ?? 'The starter offers could not be created.') : null,
                !$blogSyncOk ? (string) ($blogSync['message'] ?? 'The launch blog could not be created.') : null,
            ])));

            $run->forceFill([
                'status' => $generationOk ? 'ready' : 'failed',
                'output_json' => array_merge($draft, [
                    'engine_sync' => $engineSync,
                    'starter_sync' => $starterSync,
                    'blog_sync' => $blogSync,
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

        $this->logAutopilotStep(($result['ok'] ?? false) ? 'generate.completed' : 'generate.failed', $founder, [
            'run_id' => $result['run']->id ?? null,
            'message' => (string) ($result['error'] ?? ''),
        ], ($result['ok'] ?? false) ? 'info' : 'warning');

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
        $intelligence = $company->intelligence;
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
        $catalogItems = $this->catalogItemsFromWebsiteBuild(
            $websiteBuild,
            $websiteMode,
            $intelligence,
            (string) $blueprint->code,
            $companyName,
            $coreOffer
        );
        $pages = $this->pagePlan($blueprint, $websiteBuild);
        $tasks = $this->stringList($blueprint->default_tasks_json ?? []);
        $imageQueries = $this->imageQueries($blueprint, $company, $icpName, $websiteBuild);
        $pricing = $this->draftPricing($blueprint, $websiteMode, $companyName, $catalogItems);
        $primaryCta = trim((string) (($websiteBuild['primary_cta'] ?? '') !== '' ? $websiteBuild['primary_cta'] : ($blueprint->default_cta_json['primary'] ?? ($websiteMode === 'service' ? 'Book now' : 'Shop now'))));
        $secondaryCta = trim((string) ($blueprint->default_cta_json['secondary'] ?? 'See how it works'));
        $websitePath = trim((string) ($company->website_path ?: Str::slug($companyName)));
        $themeRankings = $this->rankThemes(
            $this->websiteProvisioningService->availableThemes($engine),
            $this->imageDirectionText($websiteBuild) . ' ' . trim((string) ($websiteBuild['special_requests'] ?? '')),
            (string) ($intelligence?->visual_style ?? ''),
            (string) $blueprint->code,
            $websiteMode,
            $engine
        );
        $theme = $themeRankings[0] ?? ['id' => '1', 'label' => 'Theme 1'];
        $websiteGoal = trim((string) ($websiteBuild['website_goal'] ?? ''));
        $faqPoints = $this->websiteBuildFaqQuestions($websiteBuild);
        $proofPoints = $this->websiteBuildTrustPoints($websiteBuild);
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

        $starterBlog = $this->starterBlogDraft($companyName, $problemSolved, $starterTitle, $icpName, $city);

        $draft = [
            'website_engine' => $engine,
            'website_mode' => $websiteMode,
            'website_title' => $companyName,
            'website_path' => $websitePath !== '' ? $websitePath : 'your-business',
            'theme_template' => $theme['id'],
            'theme_label' => $theme['label'],
            'theme_match_reasons' => array_values((array) ($theme['match_reasons'] ?? [])),
            'theme_candidates' => array_values(array_map(function (array $candidate): array {
                return [
                    'id' => (string) ($candidate['id'] ?? ''),
                    'label' => (string) ($candidate['label'] ?? ''),
                    'score' => (int) ($candidate['score'] ?? 0),
                    'match_reasons' => array_values((array) ($candidate['match_reasons'] ?? [])),
                ];
            }, array_slice($themeRankings, 0, 3))),
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
            'starter_blog' => $starterBlog,
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
            'quality_audit' => [
                'theme_strength' => count((array) ($theme['match_reasons'] ?? [])) >= 2 ? 'strong' : 'moderate',
                'theme_match_reasons' => array_values((array) ($theme['match_reasons'] ?? [])),
                'offers_count' => count($catalogItems),
                'faq_count' => count($faqPoints),
                'proof_count' => count($proofPoints),
                'blog_count' => $starterBlog['title'] !== '' ? 1 : 0,
                'page_count' => count($pages),
                'media_slot_count' => 6,
                'cta_ready' => $primaryCta !== '',
                'menus' => [
                    'online_booking' => $this->enableOnlineBooking(['website_mode' => $websiteMode]),
                    'service_menu' => $this->enableServiceMenu(['website_mode' => $websiteMode, 'website_engine' => $engine]),
                    'shop_menu' => $this->enableShopMenu(['website_mode' => $websiteMode]),
                ],
            ],
        ];

        $normalizedPayload = $this->normalizedAutopilotPayload(
            $founder,
            $company,
            $brief,
            $icp,
            $draft,
            $websiteBuild,
            $intelligence
        );

        $draft['autopilot_schema_version'] = '1.0';
        $draft['normalized_payload'] = $normalizedPayload;
        $draft['platform_checklist'] = [
            'servio' => $this->websiteAutopilotSchemaService->servioChecklist(),
            'bazaar' => $this->websiteAutopilotSchemaService->bazaarChecklist(),
        ];
        $draft['platform_payload_map'] = [
            'servio' => $this->websiteAutopilotSchemaService->servioPayloadMap(),
            'bazaar' => $this->websiteAutopilotSchemaService->bazaarPayloadMap(),
        ];
        $draft['pipeline_trace'] = [[
            'step' => 'draft.built',
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'details' => [
                'engine' => $engine,
                'mode' => $websiteMode,
                'path' => $draft['website_path'],
                'theme_template' => $draft['theme_template'],
            ],
        ]];

        return $draft;
    }

    private function syncDraftToWebsiteEngine(Founder $founder, array &$draft): array
    {
        $this->logAutopilotStep('engine_sync.start', $founder, [
            'engine' => (string) ($draft['website_engine'] ?? ''),
            'path' => (string) ($draft['website_path'] ?? ''),
        ]);

        $founder->loadMissing('businessBrief', 'company');
        $brief = $founder->businessBrief;
        $websiteBuild = $brief ? $this->websiteBuildConfig($brief) : [];
        $mediaState = $this->resolvedWebsiteMediaState($founder, $draft);
        $draft['atlas_handoff'] = array_merge(
            is_array($draft['atlas_handoff'] ?? null) ? $draft['atlas_handoff'] : [],
            ['asset_slots' => array_values(array_filter((array) ($mediaState['asset_slots'] ?? []), fn ($item) => is_array($item)))]
        );
        $draft['media_assets'] = array_values(array_filter((array) ($mediaState['media_assets'] ?? []), fn ($item) => is_array($item)));
        $draftAssetSlots = array_values(array_filter((array) ($draft['atlas_handoff']['asset_slots'] ?? []), fn ($item) => is_array($item)));
        $normalizedPayload = is_array($draft['normalized_payload'] ?? null) ? $draft['normalized_payload'] : [];
        if ($normalizedPayload !== []) {
            $normalizedPayload['media']['media_assets'] = array_values(array_filter((array) ($draft['media_assets'] ?? []), fn ($item) => is_array($item)));
            $normalizedPayload['media']['media_queries'] = $draftAssetSlots;
            $draft['normalized_payload'] = $normalizedPayload;
        }

        $repairSummary = $this->selfHealDraftBeforePreflight($founder, $draft, $websiteBuild);
        $normalizedPayload = is_array($draft['normalized_payload'] ?? null) ? $draft['normalized_payload'] : [];
        if (($repairSummary['attempts'] ?? 0) > 0) {
            $this->logAutopilotStep('preflight.repair_attempted', $founder, [
                'attempts' => (int) ($repairSummary['attempts'] ?? 0),
                'changes' => (array) ($repairSummary['changes'] ?? []),
            ]);
            $this->appendDraftTrace($draft, 'preflight.repair_attempted', 'ok', [
                'attempts' => (int) ($repairSummary['attempts'] ?? 0),
                'changes' => (array) ($repairSummary['changes'] ?? []),
            ]);
        }

        $validation = $this->websiteAutopilotValidatorService->validateNormalizedPayload($normalizedPayload);
        if (!($validation['ok'] ?? false)) {
            $qualityGate = $this->websiteAutopilotQualityGateService->summarize($normalizedPayload, $validation);
            $message = (string) ($qualityGate['summary'] ?? 'Website autopilot preflight failed.');
            $this->logAutopilotStep('preflight.failed', $founder, [
                'engine' => (string) ($draft['website_engine'] ?? ''),
                'missing' => (array) ($validation['missing'] ?? []),
                'issues' => (array) ($qualityGate['issues'] ?? []),
                'readiness_score' => (int) ($qualityGate['readiness_score'] ?? 0),
            ], 'warning');
            $this->appendDraftTrace($draft, 'preflight.failed', 'failed', [
                'missing' => (array) ($validation['missing'] ?? []),
                'issues' => (array) ($qualityGate['issues'] ?? []),
                'summary' => (string) ($qualityGate['summary'] ?? ''),
                'readiness_score' => (int) ($qualityGate['readiness_score'] ?? 0),
            ]);
            $draft['quality_gate'] = $qualityGate;
            $draft['quality_audit']['readiness_score'] = (int) ($qualityGate['readiness_score'] ?? 0);

            return [
                'ok' => false,
                'message' => $message,
                'public_url' => '',
                'media_assets_count' => count((array) ($draft['media_assets'] ?? [])),
                'quality_gate' => $qualityGate,
            ];
        }

        $this->logAutopilotStep('preflight.ok', $founder, [
            'engine' => (string) ($draft['website_engine'] ?? ''),
        ]);
        $this->appendDraftTrace($draft, 'preflight.ok', 'ok', [
            'engine' => (string) ($draft['website_engine'] ?? ''),
        ]);
        $draft['quality_gate'] = $this->websiteAutopilotQualityGateService->summarize($normalizedPayload, $validation);
        $draft['quality_audit']['readiness_score'] = (int) ($draft['quality_gate']['readiness_score'] ?? 100);

        $enginePayload = $this->websiteAutopilotMapperService->mapWebsiteUpdatePayload($founder, $draft, $websiteBuild);

        $this->appendDraftTrace($draft, 'engine_sync.payload_ready', 'ok', [
            'engine' => (string) ($draft['website_engine'] ?? ''),
            'path' => (string) ($draft['website_path'] ?? ''),
            'faq_count' => count((array) ($enginePayload['faq_items'] ?? [])),
            'testimonial_count' => count((array) ($enginePayload['testimonials'] ?? [])),
            'feature_count' => count((array) ($enginePayload['feature_items'] ?? [])),
            'media_assets_count' => count((array) ($enginePayload['media_assets'] ?? [])),
        ]);

        $result = $this->websiteProvisioningService->applyWebsiteSetup($founder, $enginePayload);

        $this->logAutopilotStep(($result['ok'] ?? false) ? 'engine_sync.ok' : 'engine_sync.failed', $founder, [
            'engine' => (string) ($draft['website_engine'] ?? ''),
            'public_url' => (string) ($result['public_url'] ?? ''),
            'message' => (string) ($result['error'] ?? ''),
        ], ($result['ok'] ?? false) ? 'info' : 'warning');

        $this->appendDraftTrace($draft, 'engine_sync.result', ($result['ok'] ?? false) ? 'ok' : 'failed', [
            'engine' => (string) ($draft['website_engine'] ?? ''),
            'public_url' => (string) ($result['public_url'] ?? ''),
            'message' => (string) ($result['error'] ?? ''),
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['error'] ?? ''),
            'public_url' => (string) ($result['public_url'] ?? ''),
            'media_assets_count' => 0,
        ];
    }

    private function selfHealDraftBeforePreflight(Founder $founder, array &$draft, array $websiteBuild): array
    {
        $changes = [];
        $attempts = 0;

        for ($pass = 1; $pass <= 4; $pass++) {
            $attempts = $pass;
            $normalized = is_array($draft['normalized_payload'] ?? null) ? $draft['normalized_payload'] : [];
            if ($normalized === []) {
                break;
            }

            $passChanges = [];
            $companyName = trim((string) ($draft['website_title'] ?? $founder->company?->company_name ?? $founder->full_name));
            $city = trim((string) ($normalized['market']['city'] ?? $founder->company?->primary_city ?? ''));
            $icpName = trim((string) ($normalized['market']['primary_icp_name'] ?? 'local customers'));
            $problemSolved = trim((string) ($draft['funnel_blocks']['problem']['body'] ?? ''));

            $mediaState = $this->resolvedWebsiteMediaState($founder, $draft);
            $currentMediaAssets = array_values(array_filter((array) ($normalized['media']['media_assets'] ?? []), fn ($item) => is_array($item)));
            $repairedMediaAssets = array_values(array_filter((array) ($mediaState['media_assets'] ?? []), fn ($item) => is_array($item)));
            if ($repairedMediaAssets !== [] && $this->mediaAssetsNeedRepair($currentMediaAssets) && $currentMediaAssets !== $repairedMediaAssets) {
                $normalized['media']['media_assets'] = $repairedMediaAssets;
                $draft['media_assets'] = $normalized['media']['media_assets'];
                $passChanges[] = 'media.media_assets';
            }
            $repairedAssetSlots = array_values(array_filter((array) ($mediaState['asset_slots'] ?? []), fn ($item) => is_array($item)));
            $currentMediaQueries = array_values(array_filter((array) ($normalized['media']['media_queries'] ?? []), fn ($item) => is_array($item)));
            if ($repairedAssetSlots !== [] && $currentMediaQueries !== $repairedAssetSlots) {
                $normalized['media']['media_queries'] = $repairedAssetSlots;
                $draft['atlas_handoff']['asset_slots'] = $normalized['media']['media_queries'];
                $passChanges[] = 'media.media_queries';
            }

            if (trim((string) ($normalized['story']['about_content'] ?? '')) === '') {
                $normalized['story']['about_content'] = $this->aboutContent($draft);
                $passChanges[] = 'story.about_content';
            }

            if (trim((string) ($normalized['hero']['hero_headline'] ?? '')) === '') {
                $normalized['hero']['hero_headline'] = (string) ($draft['hero']['headline'] ?? '');
                $passChanges[] = 'hero.hero_headline';
            }
            if (trim((string) ($normalized['hero']['hero_subhead'] ?? '')) === '') {
                $normalized['hero']['hero_subhead'] = (string) ($draft['hero']['subhead'] ?? '');
                $passChanges[] = 'hero.hero_subhead';
            }
            if (trim((string) ($normalized['hero']['hero_brief'] ?? '')) === '') {
                $normalized['hero']['hero_brief'] = (string) ($draft['hero']['brief'] ?? '');
                $passChanges[] = 'hero.hero_brief';
            }

            $catalogItems = array_values(array_filter((array) ($normalized['catalog']['items'] ?? []), fn ($item) => is_array($item)));
            if ($catalogItems === []) {
                $catalogItems = array_values(array_filter((array) ($draft['catalog_items'] ?? []), fn ($item) => is_array($item)));
            }
            if ($catalogItems !== []) {
                $normalizedCatalog = $this->normalizeCatalogItems($catalogItems);
                if ($normalizedCatalog !== $catalogItems) {
                    $passChanges[] = 'catalog.items.normalized';
                }
                if (count($normalizedCatalog) < 3) {
                    $generatedExtras = $this->generatedCatalogItems(
                        $websiteBuild,
                        (string) ($draft['website_mode'] ?? 'service'),
                        $founder->company?->intelligence,
                        (string) ($founder->company?->verticalBlueprint?->code ?? ''),
                        $companyName,
                        (string) ($normalized['conversion']['core_offer'] ?? '')
                    );
                    foreach ($generatedExtras as $candidate) {
                        if (!is_array($candidate)) {
                            continue;
                        }
                        $candidateTitle = trim((string) ($candidate['title'] ?? ''));
                        $exists = collect($normalizedCatalog)->contains(
                            fn (array $item): bool => strcasecmp(trim((string) ($item['title'] ?? '')), $candidateTitle) === 0
                        );
                        if (!$exists) {
                            $normalizedCatalog[] = $candidate;
                        }
                        if (count($normalizedCatalog) >= 3) {
                            break;
                        }
                    }
                    $passChanges[] = 'catalog.items.expanded';
                }

                $normalized['catalog']['items'] = array_values($normalizedCatalog);
                $draft['catalog_items'] = array_values($normalizedCatalog);
                if (!empty($normalized['catalog']['items'])) {
                    $passChanges[] = 'catalog.items';
                }
            }

            $featureItems = array_values(array_filter((array) ($normalized['trust']['feature_items'] ?? []), fn ($item) => is_array($item)));
            if ($featureItems === []) {
                $featureItems = $this->featureItems($draft);
            }
            if (count($featureItems) < 3) {
                foreach (array_slice((array) ($draft['sections'] ?? []), 0, 3) as $section) {
                    if (!is_array($section)) {
                        continue;
                    }
                    $title = trim((string) ($section['title'] ?? ''));
                    $body = trim((string) ($section['body'] ?? ''));
                    if ($title === '' || $body === '') {
                        continue;
                    }
                    $exists = collect($featureItems)->contains(
                        fn (array $item): bool => strcasecmp(trim((string) ($item['title'] ?? '')), $title) === 0
                    );
                    if (!$exists) {
                        $featureItems[] = ['title' => $title, 'description' => $body];
                    }
                    if (count($featureItems) >= 3) {
                        break;
                    }
                }
            }
            $normalized['trust']['feature_items'] = array_values($featureItems);
            if (!empty($normalized['trust']['feature_items'])) {
                $passChanges[] = 'trust.feature_items';
            }

            $faqItems = array_values(array_filter((array) ($normalized['trust']['faq_items'] ?? []), fn ($item) => is_array($item)));
            if ($faqItems === []) {
                $faqItems = $this->faqItems($draft);
            }
            if (count($faqItems) < 5) {
                $fallbackFaqs = collect([
                    'How does it work?',
                    'Who is this best for?',
                    'What is included?',
                    'How much does it cost?',
                    'What happens next?',
                ])->map(fn (string $question): array => [
                    'question' => $question,
                    'answer' => $this->faqAnswer($question, $companyName, (string) ($draft['starter_offer']['title'] ?? ''), $problemSolved, $icpName, $city),
                ])->all();
                foreach ($fallbackFaqs as $candidateFaq) {
                    $exists = collect($faqItems)->contains(
                        fn (array $item): bool => strcasecmp(trim((string) ($item['question'] ?? '')), trim((string) ($candidateFaq['question'] ?? ''))) === 0
                    );
                    if (!$exists) {
                        $faqItems[] = $candidateFaq;
                    }
                    if (count($faqItems) >= 5) {
                        break;
                    }
                }
            }
            if ($faqItems !== []) {
                $normalized['trust']['faq_items'] = $faqItems;
                $draft['funnel_blocks']['faq'] = $faqItems;
                $passChanges[] = 'trust.faq_items';
            }

            $testimonialItems = array_values(array_filter((array) ($normalized['trust']['testimonials'] ?? []), fn ($item) => is_array($item)));
            if ($testimonialItems === []) {
                $testimonialItems = $this->testimonials($draft);
            }
            if (count($testimonialItems) < 3) {
                $fallbackTestimonials = collect($this->defaultProofBullets(
                    $companyName,
                    (string) ($draft['starter_offer']['title'] ?? 'the main offer'),
                    [],
                    $city
                ))->map(fn (string $bullet, int $index): array => [
                    'name' => $this->testimonialName($index),
                    'position' => 'Verified customer',
                    'description' => $bullet,
                    'star' => 5,
                ])->all();
                foreach ($fallbackTestimonials as $candidateTestimonial) {
                    $testimonialItems[] = $candidateTestimonial;
                    if (count($testimonialItems) >= 3) {
                        break;
                    }
                }
            }
            if ($testimonialItems !== []) {
                $normalized['trust']['testimonials'] = array_slice($testimonialItems, 0, 3);
                $passChanges[] = 'trust.testimonials';
            }

            if (trim((string) ($normalized['blog']['blog_title'] ?? '')) === '' || trim((string) ($normalized['blog']['blog_body'] ?? '')) === '') {
                $starterBlog = $this->starterBlogDraft(
                    $companyName,
                    $problemSolved,
                    (string) ($draft['starter_offer']['title'] ?? ''),
                    $icpName,
                    $city
                );
                $normalized['blog']['blog_title'] = trim((string) ($normalized['blog']['blog_title'] ?? '')) !== ''
                    ? (string) $normalized['blog']['blog_title']
                    : (string) ($starterBlog['title'] ?? '');
                $normalized['blog']['blog_body'] = trim((string) ($normalized['blog']['blog_body'] ?? '')) !== ''
                    ? (string) $normalized['blog']['blog_body']
                    : (string) ($starterBlog['description'] ?? '');
                $normalized['blog']['blog_excerpt'] = trim((string) ($normalized['blog']['blog_excerpt'] ?? '')) !== ''
                    ? (string) $normalized['blog']['blog_excerpt']
                    : (string) Str::limit(strip_tags((string) ($starterBlog['description'] ?? '')), 220, '');
                $draft['starter_blog'] = [
                    'title' => (string) $normalized['blog']['blog_title'],
                    'description' => (string) $normalized['blog']['blog_body'],
                ];
                $passChanges[] = 'blog.blog_body';
            }

            if (trim((string) ($normalized['blog']['blog_featured_image'] ?? '')) === '') {
                $blogMedia = $this->starterBlogMedia($draft);
                $normalized['blog']['blog_featured_image'] = (string) ($blogMedia[0]['source_url'] ?? $normalized['media']['hero_banner'] ?? '');
                $normalized['blog']['blog_featured_image_alt'] = trim((string) ($normalized['blog']['blog_featured_image_alt'] ?? '')) !== ''
                    ? (string) $normalized['blog']['blog_featured_image_alt']
                    : 'Blog featured image';
                if (trim((string) ($normalized['blog']['blog_featured_image'] ?? '')) !== '') {
                    $passChanges[] = 'blog.blog_featured_image';
                }
            }

            if (trim((string) ($normalized['blog']['blog_body'] ?? '')) !== '' && mb_strlen(strip_tags((string) ($normalized['blog']['blog_body'] ?? ''))) < 1200) {
                $starterBlog = $this->starterBlogDraft(
                    $companyName,
                    $problemSolved,
                    (string) ($draft['starter_offer']['title'] ?? ''),
                    $icpName,
                    $city
                );
                $normalized['blog']['blog_body'] = (string) ($starterBlog['description'] ?? $normalized['blog']['blog_body']);
                $draft['starter_blog']['description'] = (string) $normalized['blog']['blog_body'];
                $passChanges[] = 'blog.blog_body.enriched';
            }

            if (trim((string) ($normalized['contact']['contact_email'] ?? '')) === '') {
                $fallbackEmail = trim((string) ($websiteBuild['contact_email'] ?? ''));
                if ($fallbackEmail === '') {
                    $fallbackEmail = trim((string) ($founder->email ?? ''));
                }
                if ($fallbackEmail === '' && str_contains((string) ($founder->username ?? ''), '@')) {
                    $fallbackEmail = trim((string) $founder->username);
                }
                $normalized['contact']['contact_email'] = $fallbackEmail;
                if (trim((string) ($normalized['contact']['contact_email'] ?? '')) !== '') {
                    $passChanges[] = 'contact.contact_email';
                }
            }
            if (trim((string) ($normalized['contact']['contact_phone'] ?? '')) === '') {
                $fallbackPhone = trim((string) ($websiteBuild['contact_phone'] ?? $websiteBuild['contact_phone_number'] ?? $websiteBuild['contact_mobile'] ?? ''));
                if ($fallbackPhone === '') {
                    $fallbackPhone = trim((string) ($founder->phone ?? ''));
                }
                if ($fallbackPhone === '') {
                    $fallbackPhone = trim((string) ($websiteBuild['whatsapp_number'] ?? ''));
                }
                $normalized['contact']['contact_phone'] = $fallbackPhone;
                if (trim((string) ($normalized['contact']['contact_phone'] ?? '')) !== '') {
                    $passChanges[] = 'contact.contact_phone';
                }
            }
            if (trim((string) ($normalized['contact']['business_address'] ?? '')) === '') {
                $normalized['contact']['business_address'] = (string) ($websiteBuild['business_address'] ?? '');
                if (trim((string) ($normalized['contact']['business_address'] ?? '')) !== '') {
                    $passChanges[] = 'contact.business_address';
                }
            }
            if (trim((string) ($normalized['contact']['business_hours'] ?? '')) === '') {
                $normalized['contact']['business_hours'] = (string) ($websiteBuild['business_hours'] ?? '');
                if (trim((string) ($normalized['contact']['business_hours'] ?? '')) !== '') {
                    $passChanges[] = 'contact.business_hours';
                }
            }
            if (trim((string) ($normalized['contact']['whatsapp_number'] ?? '')) === '') {
                $fallbackWhatsapp = trim((string) ($websiteBuild['whatsapp_number'] ?? ''));
                if ($fallbackWhatsapp === '') {
                    $fallbackWhatsapp = trim((string) ($normalized['contact']['contact_phone'] ?? ''));
                }
                $normalized['contact']['whatsapp_number'] = $fallbackWhatsapp;
                if (trim((string) ($normalized['contact']['whatsapp_number'] ?? '')) !== '') {
                    $passChanges[] = 'contact.whatsapp_number';
                }
            }

            if (trim((string) ($normalized['seo']['meta_title'] ?? '')) === '') {
                $normalized['seo']['meta_title'] = $this->websiteMetaTitle($draft);
                $passChanges[] = 'seo.meta_title';
            }
            if (trim((string) ($normalized['seo']['meta_description'] ?? '')) === '') {
                $normalized['seo']['meta_description'] = $this->websiteMetaDescription($draft);
                $passChanges[] = 'seo.meta_description';
            }

            $themeChanges = $this->improveWeakThemeAndOfferDraft($founder, $draft, $normalized, $websiteBuild);
            $passChanges = array_values(array_unique(array_merge($passChanges, $themeChanges)));

            $draft['normalized_payload'] = $normalized;
            $changes = array_values(array_unique(array_merge($changes, $passChanges)));

            $validation = $this->websiteAutopilotValidatorService->validateNormalizedPayload($normalized);
            if (($validation['ok'] ?? false) === true) {
                break;
            }

            if ($passChanges === []) {
                break;
            }
        }

        return [
            'attempts' => $attempts,
            'changes' => $changes,
        ];
    }

    private function improveWeakThemeAndOfferDraft(Founder $founder, array &$draft, array &$normalized, array $websiteBuild): array
    {
        $changes = [];
        $company = $founder->company;
        $engine = (string) ($draft['website_engine'] ?? 'servio');
        $websiteMode = (string) ($draft['website_mode'] ?? 'service');
        $visualSignals = trim(implode(' ', array_filter([
            (string) ($normalized['brand']['visual_direction'] ?? ''),
            (string) ($normalized['brand']['brand_voice'] ?? ''),
            (string) ($normalized['market']['niche'] ?? ''),
            (string) ($company?->intelligence?->visual_style ?? ''),
            (string) ($websiteBuild['special_requests'] ?? ''),
        ])));

        $themeRankings = $this->rankThemes(
            $this->websiteProvisioningService->availableThemes($engine),
            $visualSignals,
            (string) ($company?->intelligence?->visual_style ?? ''),
            (string) ($company?->verticalBlueprint?->code ?? ''),
            $websiteMode,
            $engine
        );

        $currentTheme = (string) ($draft['theme_template'] ?? '');
        $currentThemeCandidate = collect((array) ($draft['theme_candidates'] ?? []))
            ->firstWhere('id', $currentTheme);
        $currentScore = (int) (is_array($currentThemeCandidate) ? ($currentThemeCandidate['score'] ?? 0) : 0);
        $bestTheme = $themeRankings[0] ?? null;

        if (is_array($bestTheme) && !empty($bestTheme['id']) && ((string) $bestTheme['id'] !== $currentTheme || $currentScore < 8)) {
            $draft['theme_template'] = (string) $bestTheme['id'];
            $draft['theme_label'] = (string) ($bestTheme['label'] ?? ('Theme ' . $bestTheme['id']));
            $draft['theme_match_reasons'] = array_values((array) ($bestTheme['match_reasons'] ?? []));
            $draft['theme_candidates'] = array_values(array_map(function (array $candidate): array {
                return [
                    'id' => (string) ($candidate['id'] ?? ''),
                    'label' => (string) ($candidate['label'] ?? ''),
                    'score' => (int) ($candidate['score'] ?? 0),
                    'match_reasons' => array_values((array) ($candidate['match_reasons'] ?? [])),
                ];
            }, array_slice($themeRankings, 0, 3)));
            $normalized['identity']['theme_template'] = (string) $draft['theme_template'];
            $normalized['identity']['theme_label'] = (string) $draft['theme_label'];
            $draft['quality_audit']['theme_strength'] = count((array) ($bestTheme['match_reasons'] ?? [])) >= 2 ? 'strong' : 'moderate';
            $draft['quality_audit']['theme_match_reasons'] = array_values((array) ($bestTheme['match_reasons'] ?? []));
            $changes[] = 'identity.theme_template';
        }

        $starterOffer = is_array($draft['starter_offer'] ?? null) ? $draft['starter_offer'] : [];
        $starterTitle = $this->normalizeMarketingLabel((string) ($starterOffer['title'] ?? ''));
        if ($starterTitle !== '' && $starterTitle !== (string) ($starterOffer['title'] ?? '')) {
            $draft['starter_offer']['title'] = $starterTitle;
            $normalized['conversion']['starter_offer']['title'] = $starterTitle;
            $changes[] = 'conversion.starter_offer.title';
        }

        $catalogItems = array_values(array_filter((array) ($draft['catalog_items'] ?? []), fn ($item) => is_array($item)));
        $normalizedCatalog = $this->normalizeCatalogItems($catalogItems);
        if ($normalizedCatalog !== $catalogItems) {
            $draft['catalog_items'] = $normalizedCatalog;
            $normalized['catalog']['items'] = $normalizedCatalog;
            $changes[] = 'catalog.items.cleaned';
        }

        $heroHeadline = $this->normalizeMarketingLabel((string) ($draft['hero']['headline'] ?? ''));
        if ($heroHeadline !== '' && $heroHeadline !== (string) ($draft['hero']['headline'] ?? '')) {
            $draft['hero']['headline'] = $heroHeadline;
            $normalized['hero']['hero_headline'] = $heroHeadline;
            $changes[] = 'hero.hero_headline.cleaned';
        }

        $heroSubhead = $this->normalizeMarketingLabel((string) ($draft['hero']['subhead'] ?? ''));
        if ($heroSubhead !== '' && $heroSubhead !== (string) ($draft['hero']['subhead'] ?? '')) {
            $draft['hero']['subhead'] = $heroSubhead;
            $normalized['hero']['hero_subhead'] = $heroSubhead;
            $changes[] = 'hero.hero_subhead.cleaned';
        }

        $wellnessContext = $this->looksLikeYogaWellnessContext(
            (string) ($company?->company_name ?? ''),
            (string) ($company?->company_brief ?? ''),
            (string) ($normalized['market']['niche'] ?? ''),
            (string) ($draft['starter_offer']['title'] ?? '')
        );
        $city = trim((string) ($company?->primary_city ?? ''));
        if ($heroHeadline === '' || Str::length($heroHeadline) > 90 || str_contains(Str::lower($heroHeadline), Str::lower((string) ($company?->company_name ?? '')) . ' helps many')) {
            $draft['hero']['headline'] = $this->conciseHeroHeadline((string) ($company?->company_name ?? ''), $city, $wellnessContext);
            $normalized['hero']['hero_headline'] = (string) $draft['hero']['headline'];
            $changes[] = 'hero.hero_headline.shortened';
        }

        if (
            $heroSubhead === ''
            || Str::length($heroSubhead) > 165
            || str_contains(Str::lower($heroSubhead), 'sell like crazy')
        ) {
            $draft['hero']['subhead'] = $this->conciseHeroSubhead((string) ($company?->company_name ?? ''), $city, $wellnessContext);
            $normalized['hero']['hero_subhead'] = (string) $draft['hero']['subhead'];
            $changes[] = 'hero.hero_subhead.shortened';
        }

        return array_values(array_unique($changes));
    }

    private function normalizeCatalogItems(array $items): array
    {
        return collect($items)
            ->map(function (array $item): array {
                $item['title'] = $this->normalizeMarketingLabel((string) ($item['title'] ?? ''));
                $item['description'] = $this->normalizeMarketingLabel((string) ($item['description'] ?? ''));
                $item['price'] = trim((string) ($item['price'] ?? ''));
                return $item;
            })
            ->filter(fn (array $item): bool => trim((string) ($item['title'] ?? '')) !== '')
            ->unique(fn (array $item): string => Str::lower((string) ($item['title'] ?? '')))
            ->take(3)
            ->values()
            ->all();
    }

    private function normalizeMarketingLabel(string $value): string
    {
        $value = trim((string) Str::of($value)->replaceMatches('/\s+/', ' '));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\b([A-Za-z]+)(?:\s+\1\b)+/i', '$1', $value) ?? $value;
        $value = preg_replace('/\b(Starter|Core|Premium)(?:\s+\1\b)+/i', '$1', $value) ?? $value;
        $value = preg_replace('/\s{2,}/', ' ', $value) ?? $value;

        return trim((string) $value, " \t\n\r\0\x0B.-");
    }

    private function looksLikeYogaWellnessContext(string ...$signals): bool
    {
        $haystack = Str::lower(trim(implode(' ', $signals)));
        if ($haystack === '') {
            return false;
        }

        foreach (['yoga', 'wellness', 'meditation', 'breathwork', 'pilates', 'mobility', 'stretch'] as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function conciseHeroHeadline(string $companyName, string $city, bool $wellnessContext): string
    {
        if ($wellnessContext) {
            return trim('Beginner-friendly yoga for busy professionals' . ($city !== '' ? ' in ' . $city : '') . '.');
        }

        return trim($companyName . ' makes the first step feel clear, valuable, and easy.');
    }

    private function conciseHeroSubhead(string $companyName, string $city, bool $wellnessContext): string
    {
        if ($wellnessContext) {
            return trim('Calm, welcoming classes that reduce stress, improve mobility, and fit a full workweek' . ($city !== '' ? ' in ' . $city : '') . '.');
        }

        return $companyName . ' helps visitors understand the offer fast, trust the business faster, and know exactly what to do next.';
    }

    private function syncStarterOffer(Founder $founder, array $draft): array
    {
        $this->logAutopilotStep('starter_offer.start', $founder, [
            'engine' => (string) ($draft['website_engine'] ?? ''),
            'title' => (string) ($draft['starter_offer']['title'] ?? ''),
        ]);

        $starterOffer = is_array($draft['starter_offer'] ?? null) ? $draft['starter_offer'] : [];
        $title = $this->normalizeStarterTitle(
            (string) ($starterOffer['title'] ?? ''),
            (string) ($starterOffer['mode'] ?? 'service')
        );
        if ($title === '') {
            return ['ok' => false, 'message' => 'No starter offer title was generated.'];
        }

        $existing = $founder->actionPlans()
            ->where('platform', (string) $draft['website_engine'])
            ->where('title', $title)
            ->exists();

        if ($existing) {
            $this->logAutopilotStep('starter_offer.skip_existing', $founder, [
                'title' => $title,
            ]);
        }

        $payload = $this->websiteAutopilotMapperService->mapStarterRecordPayload($founder, $draft, 0);
        if (trim((string) ($payload['starter_title'] ?? '')) === '') {
            $payload['starter_title'] = $title;
        }
        if (trim((string) ($payload['starter_description'] ?? '')) === '') {
            $payload['starter_description'] = (string) ($starterOffer['description'] ?? '');
        }
        if (trim((string) ($payload['starter_price'] ?? '')) === '') {
            $payload['starter_price'] = trim((string) ($starterOffer['price'] ?? '')) !== ''
                ? (string) ($starterOffer['price'])
                : '49';
        }

        $result = $this->websiteProvisioningService->createStarterRecord($founder, $payload);

        if (!($result['ok'] ?? false)) {
            $this->logAutopilotStep('starter_offer.failed', $founder, [
                'title' => $title,
                'message' => (string) ($result['error'] ?? ''),
            ], 'warning');
            return ['ok' => false, 'message' => (string) ($result['error'] ?? 'Starter record could not be created.')];
        }

        $this->logAutopilotStep('starter_offer.ok', $founder, [
            'title' => $title,
        ]);

        if (!$existing) {
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
        }

        foreach (array_slice((array) ($draft['catalog_items'] ?? []), 1, 3) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $catalogTitle = $this->normalizeStarterTitle(
                (string) ($item['title'] ?? ''),
                (string) ($starterOffer['mode'] ?? 'service')
            );
            if ($catalogTitle === '') {
                continue;
            }

            $catalogExisting = $founder->actionPlans()
                ->where('platform', (string) $draft['website_engine'])
                ->where('title', $catalogTitle)
                ->exists();

            if ($catalogExisting) {
                continue;
            }

            $catalogPayload = $this->websiteAutopilotMapperService->mapStarterRecordPayload($founder, $draft, $index + 1, $item);
            if (trim((string) ($catalogPayload['starter_title'] ?? '')) === '') {
                $catalogPayload['starter_title'] = $catalogTitle;
            }
            if (trim((string) ($catalogPayload['starter_description'] ?? '')) === '') {
                $catalogPayload['starter_description'] = (string) ($item['description'] ?? '');
            }
            if (trim((string) ($catalogPayload['starter_price'] ?? '')) === '') {
                $catalogPayload['starter_price'] = trim((string) ($item['price'] ?? '')) !== ''
                    ? (string) ($item['price'])
                    : (trim((string) ($starterOffer['price'] ?? '')) !== '' ? (string) ($starterOffer['price']) : '49');
            }

            $this->websiteProvisioningService->createStarterRecord($founder, $catalogPayload);
        }

        return ['ok' => true, 'message' => 'Starter offer created from the website autopilot draft.'];
    }

    private function syncStarterBlog(Founder $founder, array $draft): array
    {
        $this->logAutopilotStep('starter_blog.start', $founder, [
            'engine' => (string) ($draft['website_engine'] ?? ''),
            'title' => (string) ($draft['starter_blog']['title'] ?? ''),
        ]);

        $blog = is_array($draft['starter_blog'] ?? null) ? $draft['starter_blog'] : [];
        $title = trim((string) ($blog['title'] ?? ''));
        $description = trim((string) ($blog['description'] ?? ''));

        if ($title === '' || $description === '') {
            return ['ok' => true, 'message' => 'No starter blog needed for this website build.'];
        }

        $existing = $founder->actionPlans()
            ->where('platform', (string) $draft['website_engine'])
            ->where('title', $title)
            ->exists();

        if ($existing) {
            $this->logAutopilotStep('starter_blog.skip_existing', $founder, [
                'title' => $title,
            ]);
        }

        $payload = $this->websiteAutopilotMapperService->mapStarterBlogPayload($founder, $draft);
        if (trim((string) ($payload['title'] ?? '')) === '') {
            $payload['title'] = $title;
        }
        if (trim((string) ($payload['description'] ?? '')) === '') {
            $payload['description'] = $description;
        }

        $result = $this->websiteProvisioningService->createBlogRecord($founder, $payload);

        if (!($result['ok'] ?? false)) {
            $this->logAutopilotStep('starter_blog.failed', $founder, [
                'title' => $title,
                'message' => (string) ($result['error'] ?? ''),
            ], 'warning');
            return ['ok' => false, 'message' => (string) ($result['error'] ?? 'Starter blog could not be created.')];
        }

        $this->logAutopilotStep('starter_blog.ok', $founder, [
            'title' => $title,
        ]);

        if (!$existing) {
            FounderActionPlan::create([
                'founder_id' => $founder->id,
                'title' => $title,
                'description' => $description,
                'platform' => (string) $draft['website_engine'],
                'priority' => 70,
                'status' => 'created',
                'cta_label' => 'Edit In Servio',
                'cta_url' => route('workspace.launch', ['module' => 'servio']),
            ]);
        }

        return ['ok' => true, 'message' => 'Starter blog created from the website autopilot draft.'];
    }

    private function normalizeStarterTitle(string $rawTitle, string $mode = 'service'): string
    {
        $title = trim((string) Str::of($rawTitle)->replaceMatches('/\s+/', ' '));
        if ($title === '') {
            $fallback = strtolower(trim($mode)) === 'product' ? 'Starter Product' : 'Starter Service';
            return $fallback;
        }

        return Str::limit($title, 180, '');
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

    private function normalizedAutopilotPayload(
        Founder $founder,
        Company $company,
        $brief,
        ?FounderIcpProfile $icp,
        array $draft,
        array $websiteBuild,
        ?CompanyIntelligence $intelligence
    ): array {
        $schema = $this->websiteAutopilotSchemaService->normalizedSchema();
        $hero = (array) ($draft['hero'] ?? []);
        $storyDescription = $this->storyDescription($draft);
        $blog = (array) ($draft['starter_blog'] ?? []);
        $catalog = array_values(array_filter((array) ($draft['catalog_items'] ?? []), fn ($item) => is_array($item)));
        $socialLinks = $this->socialLinks((string) ($websiteBuild['social_links'] ?? ''));
        $featureItems = $this->featureItems($draft);
        $faqItems = $this->faqItems($draft);
        $testimonials = $this->testimonials($draft);
        $mediaAssets = array_values(array_filter((array) ($draft['media_assets'] ?? []), fn ($item) => is_array($item)));
        $painPoints = $this->stringList($icp?->pain_points_json ?? []);
        $outcomes = $this->stringList($icp?->desired_outcomes_json ?? []);
        $objections = $this->stringList($icp?->objections_json ?? []);
        $proofPoints = array_values(array_filter((array) (($draft['funnel_blocks']['proof']['bullets'] ?? [])), fn ($item) => trim((string) $item) !== ''));

        return [
            'schema_keys' => $schema,
            'identity' => [
                'business_name' => (string) ($brief->business_name ?? $company->company_name ?? $founder->full_name),
                'founder_name' => (string) ($founder->full_name ?? ''),
                'brand_name' => (string) ($company->company_name ?? ''),
                'website_title' => (string) ($draft['website_title'] ?? ''),
                'website_path' => (string) ($draft['website_path'] ?? ''),
                'custom_domain' => (string) ($company->custom_domain ?? ''),
                'website_engine' => (string) ($draft['website_engine'] ?? ''),
                'website_mode' => (string) ($draft['website_mode'] ?? ''),
                'theme_template' => (string) ($draft['theme_template'] ?? ''),
                'theme_label' => (string) ($draft['theme_label'] ?? ''),
            ],
            'market' => [
                'industry' => (string) ($company->industry ?? ''),
                'niche' => (string) ($brief->business_type_detail ?? ''),
                'city' => (string) ($company->primary_city ?? $brief->location_city ?? ''),
                'state' => (string) ($company->primary_state ?? ''),
                'country' => (string) ($brief->location_country ?? ''),
                'target_audience' => (string) ($brief->target_audience ?? ''),
                'primary_icp_name' => (string) ($icp?->primary_icp_name ?? ''),
                'ideal_customer_profile' => (string) ($brief->ideal_customer_profile ?? ''),
                'pain_points' => $painPoints,
                'desired_outcomes' => $outcomes,
                'objections' => $objections,
                'competitor_summary' => (string) ($intelligence?->competitor_summary ?? ''),
                'competitor_price_range' => (string) ($intelligence?->pricing_notes ?? ''),
                'local_market_notes' => (string) ($intelligence?->local_market_notes ?? ''),
                'seo_keyword_cluster' => $this->imageQueries(
                    $company->verticalBlueprint ?: $this->fallbackBlueprint($company),
                    $company,
                    (string) ($icp?->primary_icp_name ?? ''),
                    $websiteBuild
                ),
            ],
            'brand' => [
                'brand_voice' => (string) ($intelligence?->brand_voice ?? ''),
                'brand_tone' => (string) ($websiteBuild['brand_tone'] ?? ''),
                'brand_values' => $this->textareaList((string) ($websiteBuild['brand_values'] ?? '')),
                'visual_direction' => $this->imageDirectionText($websiteBuild),
                'brand_description' => $this->websiteDescription($draft),
                'primary_color' => (string) ($websiteBuild['primary_color'] ?? ''),
                'secondary_color' => (string) ($websiteBuild['secondary_color'] ?? ''),
                'logo' => '',
                'dark_logo' => '',
                'favicon' => '',
                'og_image' => '',
            ],
            'hero' => [
                'hero_headline' => (string) ($hero['headline'] ?? ''),
                'hero_subhead' => (string) ($hero['subhead'] ?? ''),
                'hero_brief' => (string) ($hero['brief'] ?? ''),
                'hero_primary_cta' => (string) ($hero['primary_cta'] ?? ''),
                'hero_secondary_cta' => (string) ($hero['secondary_cta'] ?? ''),
                'hero_image' => (string) ($mediaAssets[0]['source_url'] ?? ''),
                'hero_image_alt' => (string) ($mediaAssets[0]['alt_text'] ?? ''),
                'hero_trust_line' => $proofPoints[0] ?? '',
            ],
            'story' => [
                'about_content' => $this->aboutContent($draft),
                'story_title' => $this->storyTitle($draft),
                'story_subtitle' => $this->storySubtitle($draft),
                'story_description' => $storyDescription,
                'story_items' => $this->storyItems($draft),
                'founder_story' => (string) ($brief->founder_story ?? ''),
                'credibility_points' => $this->textareaList((string) ($websiteBuild['credibility_points'] ?? '')),
                'philosophy' => (string) ($websiteBuild['brand_philosophy'] ?? ''),
            ],
            'conversion' => [
                'core_offer' => (string) ($brief->core_offer ?? ''),
                'offer_stack' => (string) ($draft['sell_like_crazy']['offer_stack'] ?? ''),
                'pricing_strategy' => (string) ($draft['pricing']['pricing_story'] ?? ''),
                'starter_offer' => (array) ($draft['starter_offer'] ?? []),
                'anchor_offer' => (string) ($draft['pricing']['anchor_offer'] ?? ''),
                'premium_offer' => (string) ($draft['pricing']['premium_offer'] ?? ''),
                'upsells' => array_slice($catalog, 1, 2),
                'cta_goal' => (string) ($websiteBuild['website_goal'] ?? ''),
                'booking_goal' => (string) ($websiteBuild['website_goal'] ?? ''),
                'lead_magnet' => (array) ($draft['funnel_blocks']['lead_magnet'] ?? []),
            ],
            'trust' => [
                'feature_items' => $featureItems,
                'why_choose_us_items' => $featureItems,
                'testimonials' => $testimonials,
                'faq_items' => $faqItems,
                'guarantees' => [(array) ($draft['funnel_blocks']['guarantee'] ?? [])],
                'proof_points' => $proofPoints,
                'social_links' => $socialLinks,
                'google_review_url' => (string) ($websiteBuild['google_review_url'] ?? ''),
            ],
            'contact' => [
                'contact_email' => (string) ($websiteBuild['contact_email'] ?? $founder->email ?? ''),
                'contact_phone' => (string) ($websiteBuild['contact_phone'] ?? $founder->phone ?? ''),
                'business_address' => (string) ($websiteBuild['business_address'] ?? ''),
                'business_hours' => (string) ($websiteBuild['business_hours'] ?? ''),
                'whatsapp_number' => (string) ($websiteBuild['whatsapp_number'] ?? ''),
                'contact_page_copy' => implode(' | ', $this->contactBlock($websiteBuild, (string) ($company->primary_city ?? ''), (string) ($brief->delivery_scope ?? ''))),
                'contact_image' => (string) ($mediaAssets[5]['source_url'] ?? ($mediaAssets[0]['source_url'] ?? '')),
            ],
            'seo' => [
                'meta_title' => $this->websiteMetaTitle($draft),
                'meta_description' => $this->websiteMetaDescription($draft),
                'indexable_pages' => array_values((array) ($draft['page_plan'] ?? [])),
                'internal_links' => array_values((array) ($draft['page_plan'] ?? [])),
                'blog_keyword_targets' => array_values((array) ($draft['image_queries'] ?? [])),
            ],
            'media' => [
                'media_assets' => $mediaAssets,
                'media_queries' => array_values((array) ($draft['atlas_handoff']['asset_slots'] ?? [])),
                'hero_banner' => (string) ($mediaAssets[0]['source_url'] ?? ''),
                'feature_images' => array_values(array_filter([$mediaAssets[1]['source_url'] ?? '', $mediaAssets[2]['source_url'] ?? ''])),
                'testimonial_images' => array_values(array_filter([$mediaAssets[3]['source_url'] ?? ''])),
                'blog_featured_image' => (string) ($this->starterBlogMedia($draft)[0]['source_url'] ?? ''),
                'faq_image' => (string) ($mediaAssets[4]['source_url'] ?? ''),
                'subscribe_image' => (string) ($mediaAssets[1]['source_url'] ?? ''),
                'fallback_images' => array_values(array_filter(array_map(fn ($item) => (string) ($item['source_url'] ?? ''), $mediaAssets))),
            ],
            'blog' => [
                'blog_title' => (string) ($blog['title'] ?? ''),
                'blog_slug' => (string) Str::slug((string) ($blog['title'] ?? '')),
                'blog_excerpt' => (string) Str::limit(strip_tags((string) ($blog['description'] ?? '')), 220, ''),
                'blog_body' => (string) ($blog['description'] ?? ''),
                'blog_featured_image' => (string) ($this->starterBlogMedia($draft)[0]['source_url'] ?? ''),
                'blog_featured_image_alt' => (string) ($mediaAssets[0]['alt_text'] ?? 'Blog featured image'),
                'blog_cta' => (string) ($hero['primary_cta'] ?? ''),
            ],
            'catalog' => [
                'items' => $catalog,
                'mode' => (string) ($draft['website_mode'] ?? ''),
            ],
            'toggles' => [
                'enable_online_booking' => $this->enableOnlineBooking($draft),
                'enable_service_menu' => $this->enableServiceMenu($draft),
                'enable_shop_menu' => $this->enableShopMenu($draft),
                'ratings_enabled' => true,
                'google_review_enabled' => true,
                'mobile_app_section_enabled' => true,
            ],
            'ops' => [
                'launch_checklist' => array_values((array) ($draft['launch_checklist'] ?? [])),
                'quality_audit' => (array) ($draft['quality_audit'] ?? []),
                'readiness_flags' => [
                    'has_services_or_products' => count($catalog) >= 1,
                    'has_faq' => count($faqItems) >= 1,
                    'has_testimonials' => count($testimonials) >= 1,
                    'has_blog' => trim((string) ($blog['title'] ?? '')) !== '',
                    'has_media' => count($mediaAssets) >= 1,
                ],
                'publish_status' => 'draft_ready',
            ],
        ];
    }

    private function appendDraftTrace(array &$draft, string $step, string $status, array $details = []): void
    {
        $trace = array_values(array_filter((array) ($draft['pipeline_trace'] ?? []), fn ($item) => is_array($item)));
        $trace[] = [
            'step' => $step,
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'details' => $details,
        ];
        $draft['pipeline_trace'] = $trace;
    }

    private function logAutopilotStep(string $step, Founder $founder, array $context = [], string $level = 'info'): void
    {
        $payload = array_merge([
            'step' => $step,
            'founder_id' => $founder->id,
            'company_id' => $founder->company_id,
            'username' => (string) ($founder->username ?? ''),
        ], $context);

        Log::log($level, '[WebsiteAutopilot] ' . $step, $payload);
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
        $blogPrimary = $byKey->get('blog_primary') ?? ($story ?? $features ?? $hero);
        $category = $byKey->get('category') ?? ($features ?? $hero);
        $servicePrimary = $byKey->get('service_primary') ?? ($hero ?? $features);
        $serviceDetail = $byKey->get('service_detail') ?? ($features ?? $proof ?? $hero);
        $serviceSupport = $byKey->get('service_support') ?? ($action ?? $story ?? $hero);

        return array_values(array_filter([
            $this->slotMedia('hero', 'hero banner', $hero),
            $this->slotMedia('blog_primary', 'blog feature image', $blogPrimary),
            $this->slotMedia('landing', 'landing banner', $hero),
            $this->slotMedia('faq', 'faq image', $faq),
            $this->slotMedia('story', 'story image', $story),
            $this->slotMedia('section_one', 'section one banner', $features),
            $this->slotMedia('section_two', 'section two banner', $proof),
            $this->slotMedia('section_three', 'section three banner', $action),
            $this->slotMedia('category', 'category image', $category),
            $this->slotMedia('service_primary', 'service primary image', $servicePrimary),
            $this->slotMedia('service_detail', 'service detail image', $serviceDetail),
            $this->slotMedia('service_support', 'service support image', $serviceSupport),
            $this->slotMedia('testimonial_primary', 'testimonial image', $proof ?? $story ?? $hero),
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

        if ($assetSlots === []) {
            $assetSlots = $this->fallbackAssetSlotsFromDraft($founder, $draft);
        }

        $draft['atlas_handoff'] = array_merge(
            is_array($draft['atlas_handoff'] ?? null) ? $draft['atlas_handoff'] : [],
            ['asset_slots' => $assetSlots]
        );

        $mediaAssets = $this->websiteMediaAssets($founder, $draft);
        if ($this->mediaAssetsNeedRepair($mediaAssets)) {
            $mediaAssets = $this->repairMediaAssetsFromProviders($draft, $mediaAssets, $assetSlots);
        }
        $mediaAssets = $this->ensurePublishableMediaAssets($mediaAssets, $draft);

        return [
            'asset_slots' => $assetSlots,
            'media_assets' => $mediaAssets,
        ];
    }

    private function fallbackAssetSlotsFromDraft(Founder $founder, array $draft): array
    {
        return $this->draftAssetSlotPlan($draft, $founder->company, $founder->full_name);
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

    private function ensurePublishableMediaAssets(array $mediaAssets, array $draft): array
    {
        $assets = array_values(array_filter($mediaAssets, fn ($item) => is_array($item)));
        if ($assets === []) {
            return [];
        }

        $byTarget = collect($assets)->keyBy(fn (array $item): string => trim((string) ($item['target'] ?? '')));
        $seedAssets = collect($assets)
            ->filter(fn (array $item): bool => trim((string) ($item['source_url'] ?? '')) !== '')
            ->values()
            ->all();

        $hero = $byTarget->get('hero') ?? ($seedAssets[0] ?? null);
        $story = $byTarget->get('story') ?? ($seedAssets[1] ?? $hero);
        $features = $byTarget->get('section_one') ?? $byTarget->get('category') ?? ($seedAssets[2] ?? $hero);
        $proof = $byTarget->get('section_two') ?? ($seedAssets[3] ?? $features ?? $hero);
        $action = $byTarget->get('section_three') ?? ($seedAssets[4] ?? $proof ?? $hero);
        $blog = $byTarget->get('blog_primary') ?? $story ?? $hero;

        $requiredTargets = [
            'hero' => ['label' => 'hero banner', 'asset' => $hero],
            'blog_primary' => ['label' => 'blog feature image', 'asset' => $blog],
            'category' => ['label' => 'category image', 'asset' => $features ?? $hero],
            'service_primary' => ['label' => 'service primary image', 'asset' => $hero ?? $features],
            'service_detail' => ['label' => 'service detail image', 'asset' => $features ?? $proof ?? $hero],
            'service_support' => ['label' => 'service support image', 'asset' => $action ?? $story ?? $hero],
            'section_one' => ['label' => 'section one banner', 'asset' => $features ?? $hero],
            'section_two' => ['label' => 'section two banner', 'asset' => $proof ?? $hero],
            'section_three' => ['label' => 'section three banner', 'asset' => $action ?? $hero],
        ];

        foreach ($requiredTargets as $target => $meta) {
            if (!$byTarget->has($target) && is_array($meta['asset'])) {
                $assets[] = $this->syntheticMediaVariant($meta['asset'], $target, (string) $meta['label']);
            }
        }

        $assets = array_values(array_filter($assets, fn ($item) => is_array($item) && trim((string) ($item['source_url'] ?? '')) !== ''));
        $distinctSources = collect($assets)->pluck('source_url')->map(fn ($url) => trim((string) $url))->filter()->unique()->values();

        if ($distinctSources->count() < 3) {
            $serviceSeeds = array_values(array_filter([
                $byTarget->get('service_primary') ?? $hero,
                $byTarget->get('service_detail') ?? $features ?? $hero,
                $byTarget->get('service_support') ?? $action ?? $story ?? $hero,
            ], fn ($item) => is_array($item)));

            foreach (['service_primary', 'service_detail', 'service_support'] as $index => $target) {
                $existing = collect($assets)->first(fn (array $item): bool => trim((string) ($item['target'] ?? '')) === $target);
                $baseAsset = $serviceSeeds[$index] ?? ($serviceSeeds[0] ?? $hero);
                if (!is_array($baseAsset)) {
                    continue;
                }

                $variant = $this->syntheticMediaVariant($baseAsset, $target, str_replace('_', ' ', $target));
                if ($existing) {
                    $assets = array_map(function (array $item) use ($target, $variant): array {
                        return trim((string) ($item['target'] ?? '')) === $target ? $variant : $item;
                    }, $assets);
                } else {
                    $assets[] = $variant;
                }
            }
        }

        return array_values(array_reduce($assets, function (array $carry, array $asset): array {
            $target = trim((string) ($asset['target'] ?? ''));
            if ($target === '') {
                return $carry;
            }

            $carry[$target] = $asset;
            return $carry;
        }, []));
    }

    private function mediaAssetsNeedRepair(array $mediaAssets): bool
    {
        $assets = array_values(array_filter($mediaAssets, fn ($item) => is_array($item)));
        $distinct = collect($assets)
            ->pluck('source_url')
            ->map(fn ($url) => $this->normalizeMediaSourceKey((string) $url))
            ->filter()
            ->unique()
            ->values();

        $requiredServiceTargets = ['service_primary', 'service_detail', 'service_support'];
        $presentTargets = collect($assets)
            ->pluck('target')
            ->map(fn ($target) => trim((string) $target))
            ->filter()
            ->values();

        foreach ($requiredServiceTargets as $target) {
            if (!$presentTargets->contains($target)) {
                return true;
            }
        }

        return $distinct->count() < 3;
    }

    private function repairMediaAssetsFromProviders(array $draft, array $mediaAssets, array $assetSlots): array
    {
        $assetsByTarget = collect(array_values(array_filter($mediaAssets, fn ($item) => is_array($item))))
            ->keyBy(fn (array $item): string => trim((string) ($item['target'] ?? '')));
        $slotPlan = collect($assetSlots)
            ->filter(fn ($item): bool => is_array($item))
            ->keyBy(fn (array $item): string => trim((string) ($item['slot_key'] ?? '')));

        $resolved = [];
        $used = [];

        foreach ($this->requiredMediaTargets() as $target => $meta) {
            $existing = $assetsByTarget->get($target);
            $existingKey = $this->normalizeMediaSourceKey((string) ($existing['source_url'] ?? ''));

            if (is_array($existing) && $existingKey !== '' && !in_array($existingKey, $used, true)) {
                $resolved[$target] = $existing;
                $used[] = $existingKey;
                continue;
            }

            $slot = $slotPlan->get($target);
            if (!is_array($slot)) {
                $slot = $this->fallbackSlotForTarget($target, $draft);
            }

            $candidate = is_array($slot) ? $this->resolveDistinctWebsiteMediaCandidate($slot, $used) : null;
            if (is_array($candidate)) {
                $resolved[$target] = $this->slotMedia($target, (string) ($meta['label'] ?? $target), $candidate);
                $used[] = $this->normalizeMediaSourceKey((string) ($candidate['source_url'] ?? ''));
                continue;
            }

            if (is_array($existing)) {
                $resolved[$target] = $existing;
            }
        }

        foreach ($assetsByTarget as $target => $asset) {
            if (!isset($resolved[$target])) {
                $resolved[$target] = $asset;
            }
        }

        return array_values(array_filter($resolved, fn ($item) => is_array($item)));
    }

    private function requiredMediaTargets(): array
    {
        return [
            'hero' => ['label' => 'hero banner'],
            'blog_primary' => ['label' => 'blog feature image'],
            'category' => ['label' => 'category image'],
            'service_primary' => ['label' => 'service primary image'],
            'service_detail' => ['label' => 'service detail image'],
            'service_support' => ['label' => 'service support image'],
            'section_one' => ['label' => 'section one banner'],
            'section_two' => ['label' => 'section two banner'],
            'section_three' => ['label' => 'section three banner'],
        ];
    }

    private function fallbackSlotForTarget(string $target, array $draft): ?array
    {
        $fallbackSlots = collect($this->draftAssetSlotPlan($draft))
            ->filter(fn ($item): bool => is_array($item))
            ->keyBy(fn (array $item): string => trim((string) ($item['slot_key'] ?? '')));

        return $fallbackSlots->get($target);
    }

    private function draftAssetSlotPlan(array $draft, ?Company $company = null, string $fallbackName = ''): array
    {
        $companyName = trim((string) ($draft['website_title'] ?? $company?->company_name ?? $fallbackName));
        $city = trim((string) ($company?->primary_city ?? ''));
        $problemSolved = trim((string) ($draft['funnel_blocks']['problem']['body'] ?? ''));
        $icpName = trim((string) ($draft['normalized_payload']['market']['primary_icp_name'] ?? 'local customers'));
        $pages = array_values(array_filter((array) ($draft['page_plan'] ?? []), fn ($item) => $item !== null && $item !== ''));
        $imageQueries = array_values(array_filter(array_map('strval', (array) ($draft['image_queries'] ?? []))));

        return $this->buildAssetSlots(
            $companyName,
            $city,
            $pages,
            $imageQueries,
            $problemSolved,
            $icpName
        );
    }

    private function resolveDistinctWebsiteMediaCandidate(array $slot, array $usedSources = []): ?array
    {
        $queries = $this->slotQueriesFromMediaPlan($slot);
        if ($queries === []) {
            return null;
        }

        foreach ($queries as $query) {
            $assets = array_merge(
                $this->searchWikimediaAssets($query),
                $this->searchUnsplashAssets($query),
                $this->searchPexelsAssets($query),
                $this->searchPixabayAssets($query)
            );

            $candidate = collect($assets)
                ->filter(fn ($asset): bool => is_array($asset) && trim((string) ($asset['source_url'] ?? '')) !== '')
                ->first(function (array $asset) use ($usedSources): bool {
                    $key = $this->normalizeMediaSourceKey((string) ($asset['source_url'] ?? ''));
                    return $key !== '' && !in_array($key, $usedSources, true);
                });

            if (is_array($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function slotQueriesFromMediaPlan(array $slot): array
    {
        $queries = [];
        foreach (array_merge(
            [trim((string) ($slot['query'] ?? ''))],
            array_map(fn ($item) => trim((string) $item), (array) ($slot['fallback_queries'] ?? []))
        ) as $query) {
            if ($query !== '') {
                $queries[] = preg_replace('/\s+/', ' ', $query) ?: $query;
            }
        }

        return array_values(array_unique($queries));
    }

    private function normalizeMediaSourceKey(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        return preg_replace('/#.*$/', '', $url) ?? $url;
    }

    private function searchUnsplashAssets(string $query): array
    {
        $key = trim((string) config('services.stock_media.unsplash_access_key', ''));
        if ($key === '') {
            return [];
        }

        try {
            $response = Http::timeout(20)->withHeaders([
                'Authorization' => 'Client-ID ' . $key,
                'Accept-Version' => 'v1',
            ])->get('https://api.unsplash.com/search/photos', [
                'query' => $query,
                'per_page' => 8,
                'orientation' => 'landscape',
                'content_filter' => 'high',
                'order_by' => 'relevant',
            ]);
        } catch (\Throwable $exception) {
            return [];
        }

        $results = (array) ($response->json('results') ?? []);

        return collect($results)->map(function (array $item): ?array {
            $url = trim((string) ($item['urls']['regular'] ?? ''));
            if ($url === '') {
                return null;
            }

            return [
                'provider' => 'unsplash',
                'source_url' => $url,
                'preview_url' => trim((string) ($item['urls']['small'] ?? $url)),
                'alt_text' => trim((string) ($item['alt_description'] ?? 'Website image')),
                'credit_name' => trim((string) ($item['user']['name'] ?? '')),
                'credit_url' => trim((string) ($item['user']['links']['html'] ?? '')),
            ];
        })->filter()->values()->all();
    }

    private function searchPexelsAssets(string $query): array
    {
        $key = trim((string) config('services.stock_media.pexels_api_key', ''));
        if ($key === '') {
            return [];
        }

        try {
            $response = Http::timeout(20)->withHeaders([
                'Authorization' => $key,
            ])->get('https://api.pexels.com/v1/search', [
                'query' => $query,
                'per_page' => 8,
                'orientation' => 'landscape',
            ]);
        } catch (\Throwable $exception) {
            return [];
        }

        $results = (array) ($response->json('photos') ?? []);

        return collect($results)->map(function (array $item): ?array {
            $url = trim((string) ($item['src']['large2x'] ?? ''));
            if ($url === '') {
                return null;
            }

            return [
                'provider' => 'pexels',
                'source_url' => $url,
                'preview_url' => trim((string) ($item['src']['medium'] ?? $url)),
                'alt_text' => trim((string) ($item['alt'] ?? 'Website image')),
                'credit_name' => trim((string) ($item['photographer'] ?? '')),
                'credit_url' => trim((string) ($item['url'] ?? '')),
            ];
        })->filter()->values()->all();
    }

    private function searchPixabayAssets(string $query): array
    {
        $key = trim((string) config('services.stock_media.pixabay_api_key', ''));
        if ($key === '') {
            return [];
        }

        try {
            $response = Http::timeout(20)->get('https://pixabay.com/api/', [
                'key' => $key,
                'q' => $query,
                'image_type' => 'photo',
                'orientation' => 'horizontal',
                'per_page' => 8,
                'safesearch' => 'true',
            ]);
        } catch (\Throwable $exception) {
            return [];
        }

        $results = (array) ($response->json('hits') ?? []);

        return collect($results)->map(function (array $item): ?array {
            $url = trim((string) ($item['largeImageURL'] ?? ''));
            if ($url === '') {
                return null;
            }

            return [
                'provider' => 'pixabay',
                'source_url' => $url,
                'preview_url' => trim((string) ($item['webformatURL'] ?? $url)),
                'alt_text' => trim((string) ($item['tags'] ?? 'Website image')),
                'credit_name' => trim((string) ($item['user'] ?? '')),
                'credit_url' => '',
            ];
        })->filter()->values()->all();
    }

    private function searchWikimediaAssets(string $query): array
    {
        try {
            $searchResponse = Http::timeout(20)->get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'format' => 'json',
                'generator' => 'search',
                'gsrsearch' => $query,
                'gsrlimit' => 8,
                'gsrnamespace' => 6,
                'prop' => 'imageinfo',
                'iiprop' => 'url|extmetadata',
                'iiurlwidth' => 1200,
            ]);
        } catch (\Throwable $exception) {
            return [];
        }

        $pages = collect((array) ($searchResponse->json('query.pages') ?? []));

        return $pages->map(function (array $page): ?array {
            $imageInfo = (array) (($page['imageinfo'][0] ?? null) ?: []);
            $sourceUrl = trim((string) ($imageInfo['thumburl'] ?? $imageInfo['url'] ?? ''));
            if ($sourceUrl === '') {
                return null;
            }

            $meta = (array) ($imageInfo['extmetadata'] ?? []);

            return [
                'provider' => 'wikimedia',
                'source_url' => $sourceUrl,
                'preview_url' => $sourceUrl,
                'alt_text' => trim(strip_tags((string) (($meta['ImageDescription']['value'] ?? '') ?: ($page['title'] ?? 'Website image')))),
                'credit_name' => trim(strip_tags((string) ($meta['Artist']['value'] ?? 'Wikimedia Commons'))),
                'credit_url' => trim((string) ($imageInfo['descriptionurl'] ?? '')),
            ];
        })->filter()->values()->all();
    }

    private function syntheticMediaVariant(array $asset, string $target, string $label): array
    {
        $sourceUrl = trim((string) ($asset['source_url'] ?? ''));
        $previewUrl = trim((string) ($asset['preview_url'] ?? $sourceUrl));
        $variantSuffix = '#hatchers-' . $target;

        return [
            'target' => $target,
            'label' => $label,
            'source_url' => $sourceUrl !== '' ? $sourceUrl . $variantSuffix : '',
            'preview_url' => $previewUrl !== '' ? $previewUrl . $variantSuffix : '',
            'alt_text' => trim((string) ($asset['alt_text'] ?? $label)),
            'credit_name' => trim((string) ($asset['credit_name'] ?? '')),
            'credit_url' => trim((string) ($asset['credit_url'] ?? '')),
            'provider' => trim((string) ($asset['provider'] ?? 'pexels')),
        ];
    }

    private function pickTheme(string $engine, string $imagePreferences = '', string $visualStyle = '', string $blueprintCode = '', string $websiteMode = 'service'): array
    {
        $themes = $this->websiteProvisioningService->availableThemes($engine);
        if ($themes === []) {
            return ['id' => '1', 'label' => 'Theme 1'];
        }

        $theme = $this->rankThemes($themes, $imagePreferences, $visualStyle, $blueprintCode, $websiteMode, $engine)[0] ?? null;

        if (is_array($theme) && !empty($theme['id'])) {
            return $theme;
        }

        return ['id' => '1', 'label' => 'Theme 1'];
    }

    private function rankThemes(
        array $themes,
        string $imagePreferences = '',
        string $visualStyle = '',
        string $blueprintCode = '',
        string $websiteMode = 'service',
        string $engine = 'servio'
    ): array {
        $themeProfiles = $this->themeProfiles($engine);
        $signals = strtolower(trim($imagePreferences . ' ' . $visualStyle . ' ' . $blueprintCode . ' ' . $websiteMode));

        return collect($themes)
            ->map(function (array $theme) use ($themeProfiles, $signals, $blueprintCode, $websiteMode): array {
                $id = (string) ($theme['id'] ?? '');
                $profile = $themeProfiles[$id] ?? ['tags' => [], 'modes' => [], 'verticals' => [], 'weight' => 0];
                $score = (int) ($profile['weight'] ?? 0);
                $reasons = [];

                foreach ((array) ($profile['tags'] ?? []) as $tag) {
                    if ($tag !== '' && str_contains($signals, strtolower((string) $tag))) {
                        $score += 4;
                        $reasons[] = 'tag:' . $tag;
                    }
                }

                if (in_array($websiteMode, (array) ($profile['modes'] ?? []), true)) {
                    $score += 5;
                    $reasons[] = 'mode:' . $websiteMode;
                }

                if ($blueprintCode !== '' && in_array($blueprintCode, (array) ($profile['verticals'] ?? []), true)) {
                    $score += 6;
                    $reasons[] = 'vertical:' . $blueprintCode;
                }

                return array_merge($theme, [
                    'score' => $score,
                    'match_reasons' => $reasons,
                ]);
            })
            ->sortByDesc('score')
            ->values()
            ->all();
    }

    private function themeProfiles(string $engine): array
    {
        if ($engine === 'servio') {
            return [
                '1' => ['tags' => ['classic', 'local', 'simple'], 'modes' => ['service'], 'verticals' => ['home-cleaning'], 'weight' => 1],
                '3' => ['tags' => ['clean', 'friendly', 'local'], 'modes' => ['service'], 'verticals' => ['dog-walking', 'barber-services'], 'weight' => 3],
                '5' => ['tags' => ['playful', 'bold', 'colorful'], 'modes' => ['service'], 'verticals' => ['dog-walking'], 'weight' => 2],
                '6' => ['tags' => ['warm', 'human', 'trustworthy'], 'modes' => ['service'], 'verticals' => ['barber-services', 'tutoring-coaching'], 'weight' => 5],
                '7' => ['tags' => ['dark', 'editorial', 'moody'], 'modes' => ['service'], 'verticals' => ['tutoring-coaching'], 'weight' => 5],
                '8' => ['tags' => ['friendly', 'clean', 'modern'], 'modes' => ['service'], 'verticals' => ['dog-walking', 'home-cleaning', 'barber-services'], 'weight' => 6],
                '9' => ['tags' => ['dark', 'luxury', 'cinematic'], 'modes' => ['service'], 'verticals' => ['tutoring-coaching'], 'weight' => 6],
            '10' => ['tags' => ['premium', 'structured', 'modern'], 'modes' => ['service'], 'verticals' => ['barber-services', 'tutoring-coaching'], 'weight' => 4],
            '11' => ['tags' => ['warm', 'approachable', 'community', 'calm', 'wellness', 'studio'], 'modes' => ['service'], 'verticals' => ['dog-walking', 'home-cleaning', 'tutoring-coaching'], 'weight' => 9],
            '12' => ['tags' => ['luxury', 'high-end', 'gallery'], 'modes' => ['service'], 'verticals' => ['barber-services'], 'weight' => 4],
            '14' => ['tags' => ['sleek', 'minimal', 'high-end', 'wellness', 'soft', 'editorial'], 'modes' => ['service'], 'verticals' => ['barber-services', 'tutoring-coaching'], 'weight' => 8],
                '15' => ['tags' => ['full', 'content-rich', 'trust'], 'modes' => ['service'], 'verticals' => ['tutoring-coaching', 'home-cleaning'], 'weight' => 6],
                '16' => ['tags' => ['luxury', 'dark', 'premium'], 'modes' => ['service'], 'verticals' => ['barber-services', 'tutoring-coaching'], 'weight' => 6],
                '17' => ['tags' => ['dark', 'moody', 'music', 'artist', 'goth'], 'modes' => ['service'], 'verticals' => ['tutoring-coaching'], 'weight' => 8],
            ];
        }

        return [
            '4' => ['tags' => ['clean', 'catalog', 'simple'], 'modes' => ['product'], 'verticals' => ['handmade-products'], 'weight' => 2],
            '8' => ['tags' => ['bold', 'visual', 'playful'], 'modes' => ['product'], 'verticals' => ['handmade-products'], 'weight' => 4],
            '10' => ['tags' => ['premium', 'catalog', 'modern'], 'modes' => ['product'], 'verticals' => ['handmade-products'], 'weight' => 6],
            '11' => ['tags' => ['grid', 'dense', 'catalog'], 'modes' => ['product'], 'verticals' => ['handmade-products'], 'weight' => 5],
            '12' => ['tags' => ['gallery', 'visual', 'editorial'], 'modes' => ['product'], 'verticals' => ['handmade-products'], 'weight' => 4],
            '13' => ['tags' => ['story', 'brand', 'trust'], 'modes' => ['product'], 'verticals' => ['handmade-products'], 'weight' => 5],
            '14' => ['tags' => ['sleek', 'premium', 'conversion'], 'modes' => ['product'], 'verticals' => ['handmade-products'], 'weight' => 7],
            '15' => ['tags' => ['content-rich', 'trust', 'brand'], 'modes' => ['product'], 'verticals' => ['handmade-products'], 'weight' => 4],
        ];
    }

    private function imageQueries(VerticalBlueprint $blueprint, Company $company, string $icpName, array $websiteBuild = []): array
    {
        $queries = collect((array) ($blueprint->default_image_queries_json ?? []))
            ->map(fn ($item) => trim((string) $item))
            ->filter();
        $direction = is_array($websiteBuild['image_direction'] ?? null) ? $websiteBuild['image_direction'] : [];
        $style = trim((string) ($direction['style'] ?? ''));
        $mood = trim((string) ($direction['mood'] ?? ''));
        $subjects = $this->stringList((array) ($direction['subjects'] ?? []));
        $avoid = collect($this->stringList((array) ($direction['avoid'] ?? [])))
            ->map(fn (string $item) => strtolower($item))
            ->all();
        $wellnessContext = $this->looksLikeYogaWellnessContext(
            (string) ($company->company_name ?? ''),
            (string) ($company->company_brief ?? ''),
            $icpName,
            (string) ($websiteBuild['special_requests'] ?? ''),
            (string) ($websiteBuild['services_pricing_notes'] ?? '')
        );

        if ($wellnessContext) {
            $queries->prepend(trim(implode(' ', array_filter([
                $company->primary_city ?: null,
                'yoga studio',
                'beginner class',
                $mood !== '' ? $mood : 'calm',
            ]))));
            $queries->push('wellness studio stretching');
            $queries->push('yoga instructor portrait');
            $queries->push('small group yoga class');
            $queries->push('meditation mobility practice');
        }

        if ($company->primary_city) {
            $queries->push(trim((string) $company->primary_city . ' local business'));
        }

        if ($icpName !== '' && !$this->containsAvoidSignal($avoid, ['random lifestyle photos', 'generic laptops', 'fake office meetings'])) {
            $queries->push($icpName . ' lifestyle');
        }

        foreach ($subjects as $subject) {
            $query = trim(implode(' ', array_filter([
                $company->primary_city ?: null,
                $company->company_name ?: null,
                $subject,
                $style !== '' ? $style : null,
                $mood !== '' ? $mood : null,
            ])));
            if ($query !== '') {
                $queries->push($query);
            }
        }

        foreach ($this->textareaList($this->imageDirectionText($websiteBuild)) as $preference) {
            if (str_starts_with(strtolower($preference), 'avoid:')) {
                continue;
            }
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
        $wellnessContext = $this->looksLikeYogaWellnessContext(
            $companyName,
            $problemSolved,
            $icpName,
            implode(' ', $imageQueries)
        );
        $baseQuery = $imageQueries[0] ?? ($companyName . ' local business');
        $cityHint = $city !== '' ? $city . ' ' : '';
        $secondQuery = $imageQueries[1] ?? ($baseQuery . ' customer');
        $thirdQuery = $imageQueries[2] ?? ($baseQuery . ' team');
        $fourthQuery = $imageQueries[3] ?? ($baseQuery . ' consultation');
        $fifthQuery = $imageQueries[4] ?? ($baseQuery . ' detail');
        $serviceQuery = $imageQueries[5] ?? ($baseQuery . ' service');
        $blogQuery = $imageQueries[6] ?? ($baseQuery . ' blog');

        return [
            [
                'slot_key' => 'hero',
                'slot_label' => 'Hero Banner',
                'provider' => 'pexels',
                'query' => $wellnessContext
                    ? trim($cityHint . 'yoga studio class calm movement')
                    : trim($cityHint . $baseQuery . ' storefront lifestyle'),
                'fallback_queries' => array_values(array_filter([
                    $wellnessContext ? trim($cityHint . 'beginner yoga class studio') : trim($cityHint . $baseQuery),
                    $wellnessContext ? trim('wellness studio stretching') : trim($baseQuery),
                    $wellnessContext ? trim($companyName . ' yoga') : trim($companyName . ' ' . $icpName),
                    $wellnessContext ? trim($icpName . ' yoga class') : trim($icpName . ' local service'),
                ])),
                'alt_text' => $companyName . ' hero image',
                'visual_brief' => $wellnessContext
                    ? 'Use a calm, premium yoga image with real movement, a clean studio setting, and a welcoming beginner-friendly feel.'
                    : trim('Use a strong hero image that instantly shows the business context, local trust, and the main outcome for ' . $icpName . '. Problem focus: ' . $problemSolved . '.'),
                'status' => 'requested',
            ],
            [
                'slot_key' => 'features',
                'slot_label' => 'Features Section',
                'provider' => 'pexels',
                'query' => $wellnessContext ? trim('yoga pose studio detail natural light') : trim($fifthQuery . ' professional detail'),
                'fallback_queries' => array_values(array_filter([
                    $wellnessContext ? trim('wellness studio detail') : trim($fifthQuery),
                    $wellnessContext ? trim('yoga mat stretching studio') : trim($baseQuery . ' detail'),
                    $wellnessContext ? trim($companyName . ' wellness class') : trim($companyName . ' quality service'),
                ])),
                'alt_text' => $companyName . ' features section image',
                'visual_brief' => $wellnessContext
                    ? 'Use a detailed yoga or wellness image that feels calm, elevated, and physically grounded.'
                    : 'Use a detailed image that supports the offer, process, or quality of the work.',
                'status' => 'requested',
            ],
            [
                'slot_key' => 'proof',
                'slot_label' => 'Social Proof',
                'provider' => 'pexels',
                'query' => $wellnessContext ? trim('small group yoga class smiling students') : trim($secondQuery . ' happy customer'),
                'fallback_queries' => array_values(array_filter([
                    $wellnessContext ? trim('yoga class community') : trim($secondQuery),
                    $wellnessContext ? trim('wellness class student') : trim($baseQuery . ' customer'),
                    $wellnessContext ? trim($icpName . ' stress relief class') : trim($icpName . ' satisfied customer'),
                ])),
                'alt_text' => $companyName . ' proof section image',
                'visual_brief' => 'Use a people-first image that feels like trust, satisfaction, or a real customer result.',
                'status' => 'requested',
            ],
            [
                'slot_key' => 'story',
                'slot_label' => 'Founder Story',
                'provider' => 'pexels',
                'query' => $wellnessContext ? trim('yoga instructor portrait studio') : trim($thirdQuery . ' small business owner'),
                'fallback_queries' => array_values(array_filter([
                    $wellnessContext ? trim('wellness instructor portrait') : trim($thirdQuery),
                    $wellnessContext ? trim($companyName . ' instructor') : trim($companyName . ' founder'),
                    $wellnessContext ? trim('meditation teacher studio') : trim($baseQuery . ' owner'),
                ])),
                'alt_text' => $companyName . ' story section image',
                'visual_brief' => 'Use a warm, human image that supports the About, founder, or story section.',
                'status' => 'requested',
            ],
            [
                'slot_key' => 'faq',
                'slot_label' => 'FAQ Section',
                'provider' => 'pexels',
                'query' => $wellnessContext ? trim('calm yoga studio conversation guidance') : trim($fourthQuery . ' helpful support'),
                'fallback_queries' => array_values(array_filter([
                    $wellnessContext ? trim('yoga studio front desk') : trim($fourthQuery),
                    $wellnessContext ? trim('wellness consultation calm') : trim($baseQuery . ' support'),
                    $wellnessContext ? trim($icpName . ' beginner yoga help') : trim($icpName . ' consultation'),
                ])),
                'alt_text' => $companyName . ' FAQ image',
                'visual_brief' => 'Use a calm, supportive image that fits questions, guidance, or easy next steps.',
                'status' => 'requested',
            ],
            [
                'slot_key' => 'action',
                'slot_label' => 'Call To Action',
                'provider' => 'pexels',
                'query' => $wellnessContext ? trim('person arriving for yoga class studio') : trim($baseQuery . ' booking checkout action'),
                'fallback_queries' => array_values(array_filter([
                    $wellnessContext ? trim('book yoga class') : trim($baseQuery . ' booking'),
                    $wellnessContext ? trim('yoga check in studio') : trim($baseQuery . ' appointment'),
                    $wellnessContext ? trim($companyName . ' yoga booking') : trim($companyName . ' contact'),
                ])),
                'alt_text' => $companyName . ' action section image',
                'visual_brief' => 'Use an image that reinforces momentum, action, booking, checkout, or contact.',
                'status' => 'requested',
            ],
            [
                'slot_key' => 'blog_primary',
                'slot_label' => 'Blog Feature Image',
                'provider' => 'pexels',
                'query' => $wellnessContext ? trim($cityHint . 'yoga blog wellness lifestyle studio') : trim($blogQuery . ' editorial lifestyle'),
                'fallback_queries' => array_values(array_filter([
                    $wellnessContext ? trim('yoga mat mindfulness studio') : trim($blogQuery),
                    $wellnessContext ? trim($companyName . ' yoga lifestyle') : trim($companyName . ' article image'),
                    $wellnessContext ? trim($icpName . ' wellness routine') : trim($icpName . ' lifestyle'),
                ])),
                'alt_text' => $companyName . ' blog feature image',
                'visual_brief' => 'Use an editorial image that fits long-form content and feels relevant to the business, not generic stock.',
                'status' => 'requested',
            ],
            [
                'slot_key' => 'category',
                'slot_label' => 'Category Image',
                'provider' => 'pexels',
                'query' => $wellnessContext ? trim($cityHint . 'yoga studio class overview') : trim($serviceQuery . ' category overview'),
                'fallback_queries' => array_values(array_filter([
                    $wellnessContext ? trim('group yoga class studio') : trim($serviceQuery),
                    $wellnessContext ? trim($companyName . ' class overview') : trim($companyName . ' service category'),
                ])),
                'alt_text' => $companyName . ' category image',
                'visual_brief' => 'Use an overview image that clearly represents the main category or service family.',
                'status' => 'requested',
            ],
            [
                'slot_key' => 'service_primary',
                'slot_label' => 'Service Primary Image',
                'provider' => 'pexels',
                'query' => $wellnessContext ? trim($cityHint . 'beginner yoga class indoor studio') : trim($serviceQuery . ' primary offering'),
                'fallback_queries' => array_values(array_filter([
                    $wellnessContext ? trim('yoga class instructor student') : trim($serviceQuery),
                    $wellnessContext ? trim($companyName . ' signature class') : trim($companyName . ' flagship service'),
                ])),
                'alt_text' => $companyName . ' primary service image',
                'visual_brief' => 'Use a concrete image of the main service being delivered.',
                'status' => 'requested',
            ],
            [
                'slot_key' => 'service_detail',
                'slot_label' => 'Service Detail Image',
                'provider' => 'pexels',
                'query' => $wellnessContext ? trim($cityHint . 'yoga stretch pose studio detail') : trim($serviceQuery . ' detail closeup'),
                'fallback_queries' => array_values(array_filter([
                    $wellnessContext ? trim('wellness movement detail') : trim($fifthQuery),
                    $wellnessContext ? trim($companyName . ' session detail') : trim($companyName . ' process detail'),
                ])),
                'alt_text' => $companyName . ' service detail image',
                'visual_brief' => 'Use a second image that adds variety and shows a different angle or detail of the service.',
                'status' => 'requested',
            ],
            [
                'slot_key' => 'service_support',
                'slot_label' => 'Service Support Image',
                'provider' => 'pexels',
                'query' => $wellnessContext ? trim($cityHint . 'yoga relaxation breathing class') : trim($serviceQuery . ' support experience'),
                'fallback_queries' => array_values(array_filter([
                    $wellnessContext ? trim('calm wellness studio experience') : trim($fourthQuery),
                    $wellnessContext ? trim($companyName . ' client experience') : trim($companyName . ' support service'),
                ])),
                'alt_text' => $companyName . ' supporting service image',
                'visual_brief' => 'Use a third distinct image that supports trust, experience, or the customer journey.',
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
        $wellnessContext = $this->looksLikeYogaWellnessContext($companyName, $problemSolved, (string) $blueprint->code);
        $problemSolved = $this->normalizeProblemStatement($problemSolved, $companyName, $city);

        if ($wellnessContext) {
            return $this->conciseHeroHeadline($companyName, $city, true);
        }

        if ($problemSolved !== '') {
            return $companyName . ' helps ' . Str::limit(Str::of($problemSolved)->lower()->trim(" .")->value(), 72, '') . '.';
        }

        return match ((string) $blueprint->code) {
            'dog-walking' => $companyName . ' keeps your dog active, happy, and looked after without the scheduling chaos.',
            'home-cleaning' => $companyName . ' gives busy homes a cleaner space without the back-and-forth.',
            'barber-services' => $companyName . ' makes booking your next sharp cut feel simple and immediate.',
            'tutoring-coaching' => $companyName . ' helps clients get expert guidance with a clear next step.',
            'handmade-products' => $companyName . ' brings handcrafted products to buyers with a simple offer and clear story.',
            default => $companyName . ' helps local customers move from interest to action faster.',
        };
    }

    private function heroSubhead(string $companyName, string $coreOffer, string $icpName, string $city): string
    {
        if ($this->looksLikeYogaWellnessContext($companyName, $coreOffer, $icpName)) {
            return $this->conciseHeroSubhead($companyName, $city, true);
        }

        $locationTail = $city !== '' ? ' in ' . $city : '';
        $offerLine = $coreOffer !== '' ? $coreOffer : 'a clear first offer';

        return $offerLine . ' is designed for ' . $icpName . $locationTail . ' and turns interest into a clearer, easier next step.';
    }

    private function normalizeProblemStatement(string $problemSolved, string $companyName, string $city): string
    {
        $statement = trim($problemSolved);
        if ($statement === '') {
            return '';
        }

        $statement = preg_replace('/^(we\s+help|help\s+people\s+|help\s+clients\s+|help\s+you\s+)/i', '', $statement) ?? $statement;
        $statement = preg_replace('/^' . preg_quote($companyName, '/') . '\s+helps\s+/i', '', $statement) ?? $statement;
        if ($city !== '') {
            $statement = preg_replace('/^' . preg_quote($city, '/') . '\s+/i', '', $statement) ?? $statement;
        }

        return trim((string) Str::of($statement)
            ->replaceMatches('/\s+/', ' ')
            ->trim(" ."));
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

    private function catalogItemsFromWebsiteBuild(
        array $websiteBuild,
        string $websiteMode,
        ?CompanyIntelligence $intelligence = null,
        string $blueprintCode = '',
        string $companyName = '',
        string $coreOffer = ''
    ): array
    {
        $cards = collect((array) ($websiteBuild['offer_cards'] ?? []))
            ->filter(fn ($item): bool => is_array($item))
            ->map(function (array $item): array {
                return [
                    'title' => trim((string) ($item['title'] ?? '')),
                    'price' => trim((string) ($item['price'] ?? '')),
                    'description' => trim((string) ($item['description'] ?? '')),
                ];
            })
            ->filter(fn (array $item) => $item['title'] !== '')
            ->values()
            ->all();

        if ($cards !== []) {
            return $cards;
        }

        $raw = (string) ($websiteBuild['offer_items'] ?? '');
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

        $freeformItems = $this->catalogItemsFromFounderNotes($websiteBuild);
        if ($freeformItems !== []) {
            return $freeformItems;
        }

        return $this->generatedCatalogItems($websiteBuild, $websiteMode, $intelligence, $blueprintCode, $companyName, $coreOffer);
    }

    private function catalogItemsFromFounderNotes(array $websiteBuild): array
    {
        $notes = trim(implode("\n", array_filter([
            (string) ($websiteBuild['services_pricing_notes'] ?? ''),
            (string) ($websiteBuild['special_requests'] ?? ''),
        ])));

        if ($notes === '') {
            return [];
        }

        return collect(preg_split("/\r\n|\n|\r/", $notes) ?: [])
            ->map(function (string $line): ?array {
                $normalized = trim(preg_replace('/^[\-\*\d\.\)\s]+/', '', $line) ?? '');
                if ($normalized === '') {
                    return null;
                }

                $parts = array_map('trim', preg_split('/\s+\|\s+/', $normalized) ?: []);
                if (count($parts) >= 2) {
                    return [
                        'title' => (string) ($parts[0] ?? ''),
                        'price' => (string) ($parts[1] ?? ''),
                        'description' => (string) ($parts[2] ?? ''),
                    ];
                }

                if (preg_match('/^(?<title>[^$]+?)\s+\$?(?<price>\d[\d,]*(?:\.\d{1,2})?(?:\/[A-Za-z]+)?)\s*[-:]\s*(?<description>.+)$/', $normalized, $matches)) {
                    return [
                        'title' => trim((string) ($matches['title'] ?? '')),
                        'price' => trim((string) ($matches['price'] ?? '')),
                        'description' => trim((string) ($matches['description'] ?? '')),
                    ];
                }

                return null;
            })
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['title'] ?? '')) !== '')
            ->take(4)
            ->values()
            ->all();
    }

    private function generatedCatalogItems(
        array $websiteBuild,
        string $websiteMode,
        ?CompanyIntelligence $intelligence = null,
        string $blueprintCode = '',
        string $companyName = '',
        string $coreOffer = ''
    ): array
    {
        $goal = trim((string) ($websiteBuild['website_goal'] ?? ''));
        $focusLine = trim((string) ($websiteBuild['founder_story_notes'] ?? ''));
        $pricingNotes = trim((string) ($intelligence?->pricing_notes ?? ''));
        $marketNotes = trim((string) ($intelligence?->local_market_notes ?? ''));
        $intelligenceOffer = trim((string) ($intelligence?->core_offer ?? ''));
        $seed = strtolower(trim(implode(' ', array_filter([
            $goal,
            $focusLine,
            (string) ($websiteBuild['services_pricing_notes'] ?? ''),
            (string) ($websiteBuild['special_requests'] ?? ''),
            $pricingNotes,
            $marketNotes,
            $intelligenceOffer,
            $coreOffer,
            $blueprintCode,
            $companyName,
        ]))));

        $profiles = [
            'dog' => [
                ['title' => 'Quick Relief Walk', 'price' => '$25', 'description' => 'A dependable 30-minute walk for busy weekdays and restless dogs.'],
                ['title' => 'Daily DogWalker Plan', 'price' => '$129/week', 'description' => 'Recurring weekday walks with updates and a simple schedule for busy owners.'],
                ['title' => 'Puppy Energy Reset', 'price' => '$39', 'description' => 'A higher-attention session for puppies or high-energy dogs that need structure and movement.'],
            ],
            'yoga' => [
                ['title' => 'Drop-In Yoga Class', 'price' => '$28', 'description' => 'A welcoming beginner-friendly class focused on stress relief, mobility, and feeling better fast.'],
                ['title' => 'Weekly Flow Membership', 'price' => '$89/mo', 'description' => 'A recurring plan for busy professionals who want consistent movement, calm, and accountability.'],
                ['title' => 'Private Yoga Session', 'price' => '$120', 'description' => 'A tailored one-on-one session for posture, mobility, recovery, and a more personal pace.'],
            ],
            'music' => [
                ['title' => 'Artist Growth Audit', 'price' => '$149', 'description' => 'A focused strategy session to diagnose what is blocking growth, audience building, and monetization.'],
                ['title' => 'Signature Coaching Sprint', 'price' => '$497', 'description' => 'A high-accountability package with clear actions, positioning, and revenue priorities.'],
                ['title' => 'Momentum Membership', 'price' => '$997/mo', 'description' => 'Ongoing guidance, feedback, and execution support for artists building predictable momentum.'],
            ],
            'clean' => [
                ['title' => 'Fresh Start Clean', 'price' => '$129', 'description' => 'A thorough reset for busy homes that need a dependable first clean.'],
                ['title' => 'Weekly Home Flow', 'price' => '$89/visit', 'description' => 'Recurring cleaning that keeps the home consistently guest-ready and stress-free.'],
                ['title' => 'Deep Clean Upgrade', 'price' => '$249', 'description' => 'A premium clean for neglected areas, special events, or seasonal resets.'],
            ],
            'barber' => [
                ['title' => 'Sharp Cut Session', 'price' => '$35', 'description' => 'A clean, confidence-building haircut with fast booking and a smooth in-chair experience.'],
                ['title' => 'Cut + Beard Reset', 'price' => '$55', 'description' => 'A more complete grooming package for clients who want the full polished look.'],
                ['title' => 'VIP Grooming Plan', 'price' => '$99/mo', 'description' => 'A membership-style plan for regular maintenance, priority booking, and a premium experience.'],
            ],
            'default_service' => [
                ['title' => 'Starter Service', 'price' => '$49', 'description' => 'An easy first step that reduces friction and gets the customer moving fast.'],
                ['title' => 'Core Offer', 'price' => '$149', 'description' => 'The main conversion-focused package built to solve the biggest customer problem.'],
                ['title' => 'Premium Upgrade', 'price' => '$299', 'description' => 'A higher-value option for buyers who want speed, depth, or extra support.'],
            ],
            'default_product' => [
                ['title' => 'Featured Product', 'price' => '$39', 'description' => 'The hero item positioned as the easiest first purchase.'],
                ['title' => 'Best-Seller Bundle', 'price' => '$79', 'description' => 'A stronger average-order-value offer combining the most relevant items.'],
                ['title' => 'Premium Collection', 'price' => '$129', 'description' => 'A higher-end option for customers who want the full experience.'],
            ],
        ];

        $selected = match (true) {
            str_contains($seed, 'yoga'), str_contains($seed, 'wellness'), str_contains($seed, 'meditation'), str_contains($seed, 'pilates') => $profiles['yoga'],
            str_contains($seed, 'dog') => $profiles['dog'],
            str_contains($seed, 'artist'), str_contains($seed, 'music'), str_contains($seed, 'musician') => $profiles['music'],
            str_contains($seed, 'clean') => $profiles['clean'],
            str_contains($seed, 'barber'), str_contains($seed, 'cut') => $profiles['barber'],
            str_contains($seed, 'dog-walking') => $profiles['dog'],
            str_contains($seed, 'tutoring-coaching') => $profiles['music'],
            str_contains($seed, 'home-cleaning') => $profiles['clean'],
            str_contains($seed, 'barber-services') => $profiles['barber'],
            $websiteMode === 'product' => $profiles['default_product'],
            default => $profiles['default_service'],
        };

        $starterPrice = $this->extractPriceHint($pricingNotes)
            ?: $this->extractPriceHint($marketNotes)
            ?: trim((string) ($selected[0]['price'] ?? ''));
        $corePrice = $this->extractPriceHintFromBand($pricingNotes, 2)
            ?: trim((string) ($selected[1]['price'] ?? ''));
        $premiumPrice = $this->extractPriceHintFromBand($pricingNotes, 3)
            ?: trim((string) ($selected[2]['price'] ?? ''));

        $offerLabel = trim($intelligenceOffer !== '' ? $intelligenceOffer : $coreOffer);
        if ($offerLabel !== '') {
            $headlineOffer = Str::headline(Str::of($offerLabel)->replace(['service', 'services', 'offer', 'offers', 'package', 'packages'], ' ')->trim()->value());
            if ($headlineOffer !== '') {
                $selected[0]['title'] = $headlineOffer . ' Starter';
                $selected[1]['title'] = $headlineOffer . ' Core';
                $selected[2]['title'] = $headlineOffer . ' Premium';
            }
        }

        $selected[0]['price'] = $starterPrice !== '' ? $starterPrice : $selected[0]['price'];
        $selected[1]['price'] = $corePrice !== '' ? $corePrice : $selected[1]['price'];
        $selected[2]['price'] = $premiumPrice !== '' ? $premiumPrice : $selected[2]['price'];

        return $selected;
    }

    private function extractPriceHint(string $raw): string
    {
        if (preg_match('/\$?\s?(\d[\d,]*(?:\.\d{1,2})?(?:\/[A-Za-z]+)?)/', $raw, $matches)) {
            return '$' . ltrim((string) ($matches[1] ?? ''), '$');
        }

        return '';
    }

    private function extractPriceHintFromBand(string $raw, int $band): string
    {
        preg_match_all('/\$?\s?(\d[\d,]*(?:\.\d{1,2})?(?:\/[A-Za-z]+)?)/', $raw, $matches);
        $prices = array_values(array_filter(array_map('strval', (array) ($matches[1] ?? []))));
        $index = max(0, $band - 1);

        if (!isset($prices[$index])) {
            return '';
        }

        return '$' . ltrim($prices[$index], '$');
    }

    private function starterBlogDraft(string $companyName, string $problemSolved, string $starterTitle, string $icpName, string $city): array
    {
        $cityTail = $city !== '' ? ' in ' . $city : '';
        $problem = trim($problemSolved) !== '' ? trim($problemSolved) : 'make the first buying decision feel simpler and more confident';
        $offer = trim($starterTitle) !== '' ? trim($starterTitle) : 'the best first offer';
        $relatedSubject = $this->relatedBlogSubject($starterTitle, $icpName, $city);

        return [
            'title' => Str::limit($companyName . ': ' . $relatedSubject, 180, ''),
            'description' => trim(implode("\n", [
                '<p>' . e($companyName) . ' was built to help ' . e($icpName) . $cityTail . ' solve one frustrating problem: ' . e($problem) . '. The businesses that win attention and trust are the ones that make the next step feel clear, valuable, and easy to say yes to.</p>',
                '<h2>Why this matters</h2>',
                '<p>' . e($relatedSubject) . ' matters because customers are rarely just buying a service. They are buying certainty, speed, convenience, and confidence. If a business does not explain the stakes clearly, the buyer delays, comparison shops forever, or settles for a cheaper option that creates more hassle later.</p>',
                '<h2>The common mistake people make</h2>',
                '<p>Most people try to solve the symptom instead of the real problem. They look for the fastest option, the cheapest option, or the most familiar option. But when the experience is confusing, slow, or inconsistent, they end up paying with time, frustration, and lost trust.</p>',
                '<h2>What a smarter first step looks like</h2>',
                '<p>A stronger buying path starts with a focused offer like ' . e($offer) . '. That kind of offer works because it removes friction. It gives the customer a clear starting point, helps them understand the value quickly, and reduces the anxiety that usually slows down action.</p>',
                '<h2>How direct-response thinking improves the decision</h2>',
                '<p>Strong direct-response content does three things well. It names the problem in a way the buyer already feels. It shows a believable outcome they actually want. And it frames the offer as the bridge between where they are and where they want to go. That is why websites built around clarity, proof, and urgency convert better than websites built like brochures.</p>',
                '<h2>What customers should look for before they commit</h2>',
                '<ul><li>A provider that understands the real problem, not just the category.</li><li>A clear first offer with transparent pricing and obvious next steps.</li><li>Proof that the experience is dependable, not just attractive.</li><li>A reason to act now instead of putting the decision off again.</li></ul>',
                '<h2>The practical takeaway</h2>',
                '<p>' . e($companyName) . ' uses this exact logic to help ' . e($icpName) . ' move faster with more confidence. When the offer is clear and the message is relevant, the website becomes a selling asset, not just an online placeholder. That is how a strong first impression turns into booked revenue.</p>',
                '<p>The takeaway is simple: better positioning leads to better offers, better offers lead to easier yeses, and easier yeses create momentum. That is the standard a high-conviction website should meet from day one.</p>',
            ])),
        ];
    }

    private function pagePlan(VerticalBlueprint $blueprint, array $websiteBuild): array
    {
        $defaultPages = $this->stringList($blueprint->default_pages_json ?? []);
        $requestedPages = $this->stringList((array) ($websiteBuild['page_sections'] ?? []));
        if ($requestedPages === []) {
            $requestedPages = $this->textareaList((string) ($websiteBuild['must_include_pages'] ?? ''));
        }

        return collect(array_merge($defaultPages, $requestedPages, ['about us', 'faq', 'contact']))
            ->map(fn (string $item) => trim($item))
            ->reject(fn (string $item): bool => in_array(strtolower($item), ['gallery', 'photo gallery', 'image gallery'], true))
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

    private function websiteBuildFaqQuestions(array $websiteBuild): array
    {
        $items = $this->stringList((array) ($websiteBuild['faq_questions_list'] ?? []));
        if ($items !== []) {
            return $items;
        }

        return $this->textareaList((string) ($websiteBuild['faq_points'] ?? ''));
    }

    private function websiteBuildTrustPoints(array $websiteBuild): array
    {
        $items = $this->stringList((array) ($websiteBuild['trust_points_list'] ?? []));
        if ($items !== []) {
            return $items;
        }

        return $this->textareaList((string) ($websiteBuild['proof_points'] ?? ''));
    }

    private function imageDirectionText(array $websiteBuild): string
    {
        $direction = is_array($websiteBuild['image_direction'] ?? null) ? $websiteBuild['image_direction'] : [];
        if ($direction === []) {
            return trim((string) ($websiteBuild['image_preferences'] ?? ''));
        }

        $lines = [];
        $style = trim((string) ($direction['style'] ?? ''));
        $mood = trim((string) ($direction['mood'] ?? ''));
        $subjects = $this->stringList((array) ($direction['subjects'] ?? []));
        $avoid = $this->stringList((array) ($direction['avoid'] ?? []));

        if ($style !== '') {
            $lines[] = 'Style: ' . $style;
        }
        if ($mood !== '') {
            $lines[] = 'Mood: ' . $mood;
        }
        if ($subjects !== []) {
            $lines[] = 'Show: ' . implode(', ', $subjects);
        }
        if ($avoid !== []) {
            $lines[] = 'Avoid: ' . implode(', ', $avoid);
        }

        return implode("\n", $lines);
    }

    private function containsAvoidSignal(array $avoid, array $signals): bool
    {
        foreach ($signals as $signal) {
            if (in_array(strtolower($signal), $avoid, true)) {
                return true;
            }
        }

        return false;
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

    private function enableOnlineBooking(array $draft): bool
    {
        $mode = trim((string) ($draft['website_mode'] ?? 'service'));
        return in_array($mode, ['service', 'product'], true);
    }

    private function enableServiceMenu(array $draft): bool
    {
        $mode = trim((string) ($draft['website_mode'] ?? 'service'));
        $engine = trim((string) ($draft['website_engine'] ?? 'servio'));

        return $engine === 'servio' || $mode === 'service';
    }

    private function enableShopMenu(array $draft): bool
    {
        return trim((string) ($draft['website_mode'] ?? 'service')) === 'product';
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

    private function starterBlogMedia(array $draft): array
    {
        $media = array_values(array_filter((array) ($draft['media_assets'] ?? []), fn ($item) => is_array($item)));

        if ($media === []) {
            $resolved = $this->normalizeResolvedAssets((array) ($draft['atlas_handoff']['asset_slots'] ?? []));
            $byKey = collect($resolved)->keyBy(fn (array $asset): string => trim((string) ($asset['slot_key'] ?? '')));
            $blog = $byKey->get('blog_primary') ?? $byKey->get('hero') ?? $byKey->get('story') ?? ($resolved[0] ?? null);

            return is_array($blog) && trim((string) ($blog['source_url'] ?? '')) !== '' ? [[
                'target' => 'blog_primary',
                'source_url' => trim((string) ($blog['source_url'] ?? '')),
            ]] : [];
        }

        $byTarget = collect($media)->keyBy(fn (array $item): string => trim((string) ($item['target'] ?? '')));
        $asset = $byTarget->get('blog_primary') ?? $byTarget->get('hero') ?? $byTarget->get('story') ?? $byTarget->get('section_one');

        return is_array($asset) && trim((string) ($asset['source_url'] ?? '')) !== '' ? [[
            'target' => 'blog_primary',
            'source_url' => trim((string) ($asset['source_url'] ?? '')),
        ]] : [];
    }

    private function relatedBlogSubject(string $starterTitle, string $icpName, string $city): string
    {
        $offer = trim($starterTitle) !== '' ? trim($starterTitle) : 'choosing the right service';
        $audience = trim($icpName) !== '' ? trim($icpName) : 'buyers';
        $location = trim($city) !== '' ? ' in ' . trim($city) : '';

        return 'A practical guide to ' . Str::lower($offer) . ' for ' . $audience . $location;
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
