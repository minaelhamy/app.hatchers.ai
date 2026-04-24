<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyIntelligence;
use App\Models\Founder;
use App\Models\FounderActionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OsAssistantActionService
{
    private const SESSION_KEY_PREFIX = 'os_assistant_pending_action_';

    public function handle(Founder $founder, Request $request, string $message): array
    {
        $message = trim($message);
        if ($message === '') {
            return ['handled' => false];
        }

        $readOnly = $this->handleReadOnlyQuery($founder, $message);
        if (!empty($readOnly)) {
            return array_merge(['handled' => true, 'executed' => false], $readOnly);
        }

        $pending = $this->getPendingAction($request, $founder);
        $detectedRole = $this->detectActorRoleFromText($message);

        if (!empty($pending)) {
            if ($this->isRejectionMessage($message)) {
                $this->clearPendingAction($request, $founder);

                return [
                    'handled' => true,
                    'reply' => 'Understood. I canceled that pending OS action and did not change anything.',
                    'executed' => false,
                ];
            }

            if (empty($pending['actor_role']) && $detectedRole !== '') {
                $pending['actor_role'] = $detectedRole;
                $this->setPendingAction($request, $founder, $pending);
            }

            if (empty($pending['actor_role'])) {
                return [
                    'handled' => true,
                    'reply' => 'Before I perform this action, are you acting as the founder or the mentor? Reply with "Founder, yes" or "Mentor, yes" to proceed.',
                    'executed' => false,
                ];
            }

            if (!$this->isConfirmationMessage($message)) {
                return [
                    'handled' => true,
                    'reply' => 'I have the action ready. Reply "Yes" to proceed as ' . $pending['actor_role'] . ', or say "cancel" if you want me to stop.',
                    'executed' => false,
                ];
            }

            $execution = $this->executeWriteAction($founder, $pending);
            $this->clearPendingAction($request, $founder);

            return [
                'handled' => true,
                'reply' => $execution['reply'],
                'executed' => (bool) ($execution['success'] ?? false),
                'actions' => $execution['actions'] ?? [],
            ];
        }

        $action = $this->buildWriteActionFromMessage($message);
        if (empty($action)) {
            return ['handled' => false];
        }

        $action['actor_role'] = $detectedRole;
        $action['created_at'] = now()->toIso8601String();
        $this->setPendingAction($request, $founder, $action);

        if ($detectedRole === '') {
            return [
                'handled' => true,
                'reply' => 'I can do that, but I need one safety check first. Are you acting as the founder or the mentor? Reply with "Founder, yes" or "Mentor, yes" and I will proceed.',
                'executed' => false,
            ];
        }

        return [
            'handled' => true,
            'reply' => 'I am ready to ' . $action['summary'] . '. Please reply "Yes" to proceed as ' . $detectedRole . ', or say "cancel" to stop.',
            'executed' => false,
        ];
    }

    public function createCampaignFromOs(Founder $founder, string $title, string $description, string $actorRole = 'founder'): array
    {
        $title = trim($title);
        $description = trim($description);

        if ($title === '' || $description === '') {
            return [
                'success' => false,
                'reply' => 'Campaign title and description are both required.',
            ];
        }

        $creation = $this->createPlatformRecord($founder, [
            'platform' => 'atlas',
            'category' => 'campaign',
            'title' => $title,
            'description' => $description,
            'actor_role' => $actorRole,
        ]);

        if (!($creation['success'] ?? false)) {
            return $creation;
        }

        $this->recordActionPlan(
            $founder,
            $title,
            'Atlas created a real campaign record in ATLAS from Hatchers OS.',
            'atlas',
            $creation['edit_url'] ?? '/marketing',
            $this->ctaLabelForPlatform('atlas'),
            'created'
        );

        return [
            'success' => true,
            'title' => (string) ($creation['title'] ?? $title),
            'description' => $description,
            'edit_url' => (string) ($creation['edit_url'] ?? ''),
            'reply' => $creation['reply'] ?? 'Done. I created that campaign in Atlas and linked it back into Hatchers OS.',
            'action_type' => 'platform_record_create',
            'sync_summary' => 'Atlas created a real "campaign" record in atlas from Hatchers OS.',
        ];
    }

    public function archiveCampaignFromOs(Founder $founder, string $title, string $actorRole = 'founder'): array
    {
        return $this->handleCampaignStateAction($founder, $title, 'archive', $actorRole);
    }

    public function restoreCampaignFromOs(Founder $founder, string $title, string $actorRole = 'founder'): array
    {
        return $this->handleCampaignStateAction($founder, $title, 'restore', $actorRole);
    }

    public function updateTaskStatusFromOs(Founder $founder, string $title, string $status, string $actorRole = 'founder'): array
    {
        $normalizedStatus = in_array(trim(strtolower($status)), ['complete', 'completed', 'done'], true) ? 'completed' : 'open';

        $result = $this->updatePlatformRecord($founder, [
            'platform' => 'lms',
            'category' => 'task',
            'field' => 'status',
            'value' => $normalizedStatus,
            'target_name' => trim($title),
            'actor_role' => $actorRole,
        ]);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        return [
            'success' => true,
            'reply' => $normalizedStatus === 'completed'
                ? 'Done. I marked that LMS task as completed from Hatchers OS.'
                : 'Done. I reopened that LMS task from Hatchers OS.',
            'action_type' => 'platform_record_update',
            'sync_summary' => $normalizedStatus === 'completed'
                ? 'Hatchers OS marked an LMS task as completed.'
                : 'Hatchers OS reopened an LMS task.',
        ];
    }

    public function updateMilestoneStatusFromOs(Founder $founder, string $title, string $status, string $actorRole = 'founder'): array
    {
        $normalizedStatus = in_array(trim(strtolower($status)), ['complete', 'completed', 'done'], true) ? 'completed' : 'open';

        $result = $this->updatePlatformRecord($founder, [
            'platform' => 'lms',
            'category' => 'milestone',
            'field' => 'status',
            'value' => $normalizedStatus,
            'target_name' => trim($title),
            'actor_role' => $actorRole,
        ]);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        return [
            'success' => true,
            'reply' => $normalizedStatus === 'completed'
                ? 'Done. I marked that LMS milestone as completed from Hatchers OS.'
                : 'Done. I reopened that LMS milestone from Hatchers OS.',
            'action_type' => 'platform_record_update',
            'sync_summary' => $normalizedStatus === 'completed'
                ? 'Hatchers OS marked an LMS milestone as completed.'
                : 'Hatchers OS reopened an LMS milestone.',
        ];
    }

    public function updateProductFieldFromOs(
        Founder $founder,
        string $title,
        string $field,
        string $value,
        string $actorRole = 'founder'
    ): array {
        return $this->updateCommerceFieldFromOs($founder, 'bazaar', 'product', $title, $field, $value, $actorRole);
    }

    public function updateServiceFieldFromOs(
        Founder $founder,
        string $title,
        string $field,
        string $value,
        string $actorRole = 'founder'
    ): array {
        return $this->updateCommerceFieldFromOs($founder, 'servio', 'service', $title, $field, $value, $actorRole);
    }

    public function saveCommerceConfigFromOs(
        Founder $founder,
        string $platform,
        string $category,
        string $title,
        array $attributes,
        bool $updateExisting = false,
        string $actorRole = 'founder'
    ): array {
        $title = trim($title);
        if ($title === '') {
            return [
                'success' => false,
                'reply' => 'A title is required before Hatchers OS can save that commerce config.',
            ];
        }

        $action = [
            'platform' => $platform,
            'category' => $category,
            'title' => $title,
            'description' => (string) ($attributes['description'] ?? ''),
            'actor_role' => $actorRole,
            'payload' => $attributes,
        ];

        if ($updateExisting) {
            $result = $this->updatePlatformRecord($founder, array_merge($action, [
                'field' => 'config',
                'value' => 'sync',
                'target_name' => $title,
            ]));
        } else {
            $result = $this->createPlatformRecord($founder, $action);
        }

        if (!($result['success'] ?? false)) {
            return $result;
        }

        return [
            'success' => true,
            'reply' => 'Done. I synced that ' . $category . ' into ' . ucfirst($platform) . ' from Hatchers OS.',
            'title' => $title,
            'edit_url' => (string) ($result['edit_url'] ?? ''),
            'action_type' => $updateExisting ? 'platform_record_update' : 'platform_record_create',
            'sync_summary' => 'Hatchers OS synced a ' . $category . ' config into ' . ucfirst($platform) . '.',
        ];
    }

    public function updateCommerceOperationFromOs(
        Founder $founder,
        string $platform,
        string $category,
        string $targetName,
        string $field,
        string $value,
        array $attributes = [],
        string $actorRole = 'founder'
    ): array {
        $result = $this->updatePlatformRecord($founder, [
            'platform' => $platform,
            'category' => $category,
            'field' => $field,
            'value' => $value,
            'target_name' => trim($targetName),
            'actor_role' => $actorRole,
            'payload' => $attributes,
        ]);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        return [
            'success' => true,
            'reply' => 'Done. I updated that ' . $category . ' in ' . ucfirst($platform) . ' from Hatchers OS.',
            'title' => (string) ($result['title'] ?? $targetName),
            'edit_url' => (string) ($result['edit_url'] ?? ''),
            'email_followup_sent' => (bool) ($result['email_followup_sent'] ?? false),
            'action_type' => 'platform_record_update',
            'sync_summary' => 'Hatchers OS updated a ' . $category . ' record in ' . ucfirst($platform) . '.',
        ];
    }

    private function executeWriteAction(Founder $founder, array $action): array
    {
        $type = trim((string) ($action['type'] ?? ''));
        $actorRole = trim((string) ($action['actor_role'] ?? 'founder'));
        $company = $founder->company ?: Company::create([
            'founder_id' => $founder->id,
            'company_name' => $founder->full_name . '\'s Company',
            'business_model' => 'hybrid',
            'stage' => 'idea',
            'website_status' => 'not_started',
        ]);

        if ($type === 'company_field_update') {
            $field = trim((string) ($action['field'] ?? ''));
            $value = trim((string) ($action['value'] ?? ''));

            if ($field === '' || $value === '') {
                return [
                    'success' => false,
                    'reply' => "I couldn't complete that update because the field or value was missing.",
                ];
            }

            if (in_array($field, ['company_name', 'company_brief'], true)) {
                $company->forceFill([$field => $value])->save();
            } else {
                CompanyIntelligence::updateOrCreate(
                    ['company_id' => $company->id],
                    [
                        $field => $value,
                        'intelligence_updated_at' => now(),
                    ]
                );
            }

            return [
                'success' => true,
                'reply' => 'Done. I updated "' . str_replace('_', ' ', $field) . '" in the shared Hatchers OS intelligence for this founder.',
                'actions' => [
                    [
                        'title' => 'Updated ' . str_replace('_', ' ', $field),
                        'platform' => 'os',
                    ],
                ],
                'actor_role' => $actorRole,
                'action_type' => 'company_field_update',
                'field' => $field,
                'value' => $value,
                'sync_summary' => 'Atlas updated the shared company field "' . $field . '" from Hatchers OS.',
            ];
        }

        if ($type === 'draft_record') {
            $platform = trim((string) ($action['platform'] ?? 'os'));
            $category = trim((string) ($action['category'] ?? 'task'));
            $requestText = trim((string) ($action['request'] ?? ''));
            $title = trim((string) ($action['title'] ?? 'Draft action'));

            if (in_array($category, ['product', 'service', 'task', 'milestone', 'blog', 'campaign'], true)) {
                $creation = $this->createPlatformRecord($founder, [
                    'platform' => $platform,
                    'category' => $category,
                    'title' => $title,
                    'description' => $requestText,
                    'actor_role' => $actorRole,
                ]);

                if (!($creation['success'] ?? false)) {
                    return [
                        'success' => false,
                        'reply' => $creation['reply'] ?? 'I could not create that record in the connected tool right now.',
                    ];
                }

                $this->recordActionPlan(
                    $founder,
                    $title,
                    'Atlas created a real ' . $category . ' record in ' . strtoupper($platform) . ' from Hatchers OS.',
                    $platform,
                    $creation['edit_url'] ?? '/dashboard',
                    $this->ctaLabelForPlatform($platform),
                    'created'
                );

                return [
                    'success' => true,
                    'reply' => $creation['reply'] ?? ('Done. I created that ' . $category . ' in ' . ucfirst($platform) . '.'),
                    'actions' => [
                        [
                            'title' => $creation['title'] ?? $title,
                            'platform' => $platform,
                            'url' => $creation['edit_url'] ?? null,
                        ],
                    ],
                    'actor_role' => $actorRole,
                    'action_type' => 'platform_record_create',
                    'sync_summary' => 'Atlas created a real "' . $category . '" record in ' . $platform . ' from Hatchers OS.',
                ];
            }

            FounderActionPlan::create([
                'founder_id' => $founder->id,
                'title' => $title,
                'description' => trim($requestText . "\n\nRequested by: " . $actorRole . ' via Atlas in Hatchers OS.'),
                'platform' => $platform,
                'priority' => 72,
                'status' => 'draft',
                'cta_label' => $this->ctaLabelForPlatform($platform),
                'cta_url' => $this->ctaUrlForPlatform($platform),
            ]);

            return [
                'success' => true,
                'reply' => 'Done. I created a draft ' . $category . ' action inside the founder operating plan so it can be tracked from Hatchers OS.',
                'actions' => [
                    [
                        'title' => $title,
                        'platform' => $platform,
                    ],
                ],
                'actor_role' => $actorRole,
                'action_type' => 'draft_record',
                'sync_summary' => 'Atlas created a founder operating plan draft for "' . $category . '" from Hatchers OS.',
            ];
        }

        if ($type === 'platform_record_update') {
            $platform = trim((string) ($action['platform'] ?? ''));
            $category = trim((string) ($action['category'] ?? ''));
            $field = trim((string) ($action['field'] ?? ''));
            $value = trim((string) ($action['value'] ?? ''));

            if ($platform === '' || $category === '' || $field === '' || $value === '') {
                return [
                    'success' => false,
                'reply' => "I couldn't complete that update because the target record information was incomplete.",
                ];
            }

            $update = $this->updatePlatformRecord($founder, [
                'platform' => $platform,
                'category' => $category,
                'field' => $field,
                'target_name' => (string) ($action['target_name'] ?? ''),
                'value' => $value,
                'actor_role' => $actorRole,
            ]);

            if (!($update['success'] ?? false)) {
                return [
                    'success' => false,
                    'reply' => $update['reply'] ?? 'I could not update that record in the connected tool right now.',
                ];
            }

            $this->recordActionPlan(
                $founder,
                'Updated ' . $category,
                'Atlas updated ' . (!empty($action['target_name']) ? $category . ' "' . $action['target_name'] . '"' : 'the latest ' . $category) . ' in ' . strtoupper($platform) . ' from Hatchers OS.',
                $platform,
                $update['edit_url'] ?? '/dashboard',
                $this->ctaLabelForPlatform($platform),
                'updated'
            );

            return [
                'success' => true,
                'reply' => $update['reply'] ?? ('Done. I updated the latest ' . $category . ' in ' . ucfirst($platform) . '.'),
                'actions' => [
                    [
                        'title' => $update['title'] ?? ('Updated ' . $category),
                        'platform' => $platform,
                        'url' => $update['edit_url'] ?? null,
                    ],
                ],
                'actor_role' => $actorRole,
                'action_type' => 'platform_record_update',
                'field' => $field,
                'value' => $value,
                'sync_summary' => 'Atlas updated ' . (!empty($action['target_name']) ? '"' . $action['target_name'] . '"' : 'the latest "' . $category . '" record') . ' in ' . $platform . ' from Hatchers OS.',
            ];
        }

        if ($type === 'platform_record_action') {
            $platform = trim((string) ($action['platform'] ?? ''));
            $category = trim((string) ($action['category'] ?? ''));
            $operation = trim((string) ($action['operation'] ?? ''));

            if ($platform === '' || $category === '' || $operation === '') {
                return [
                    'success' => false,
                    'reply' => "I couldn't complete that action because the target information was incomplete.",
                ];
            }

            $result = $this->actOnPlatformRecord($founder, [
                'platform' => $platform,
                'category' => $category,
                'operation' => $operation,
                'target_name' => (string) ($action['target_name'] ?? ''),
                'actor_role' => $actorRole,
            ]);

            if (!($result['success'] ?? false)) {
                return [
                    'success' => false,
                    'reply' => $result['reply'] ?? 'I could not complete that campaign action right now.',
                ];
            }

            $actionLabel = $operation === 'archive' ? 'Archived' : 'Restored';
            $operationPast = $this->operationPastTense($operation);
            $targetDescription = !empty($action['target_name'])
                ? $category . ' "' . $action['target_name'] . '"'
                : 'the requested ' . $category;

            $this->recordActionPlan(
                $founder,
                $actionLabel . ' ' . $category,
                'Atlas ' . strtolower($actionLabel) . ' ' . $targetDescription . ' from Hatchers OS.',
                $platform,
                $result['edit_url'] ?? $this->ctaUrlForPlatform($platform),
                $this->ctaLabelForPlatform($platform),
                strtolower($operation) === 'archive' ? 'archived' : 'updated'
            );

            return [
                'success' => true,
                'reply' => $result['reply'] ?? ('Done. I ' . $operation . 'd that ' . $category . ' in ' . ucfirst($platform) . '.'),
                'actions' => [
                    [
                        'title' => $result['title'] ?? ucfirst($operation) . 'd ' . $category,
                        'platform' => $platform,
                        'url' => $result['edit_url'] ?? null,
                    ],
                ],
                'actor_role' => $actorRole,
                'action_type' => 'platform_record_action',
                'sync_summary' => 'Atlas ' . $operationPast . ' ' . (!empty($action['target_name']) ? '"' . $action['target_name'] . '"' : 'a "' . $category . '"') . ' in ' . $platform . ' from Hatchers OS.',
            ];
        }

        return [
            'success' => false,
            'reply' => "I understood the request as an action, but I don't support executing that action yet.",
        ];
    }

    private function buildWriteActionFromMessage(string $message): array
    {
        $fieldPatterns = [
            'company_name' => '/\b(?:set|update|change)\s+(?:the\s+)?company\s+name\s+to\s+(.+)/i',
            'company_brief' => '/\b(?:set|update|change)\s+(?:the\s+)?(?:company\s+description|company\s+brief)\s+to\s+(.+)/i',
            'target_audience' => '/\b(?:set|update|change)\s+(?:the\s+)?target\s+audience\s+to\s+(.+)/i',
            'ideal_customer_profile' => '/\b(?:set|update|change)\s+(?:the\s+)?(?:ideal\s+customer\s+profile|icp)\s+to\s+(.+)/i',
            'brand_voice' => '/\b(?:set|update|change)\s+(?:the\s+)?brand\s+voice\s+to\s+(.+)/i',
            'differentiators' => '/\b(?:set|update|change)\s+(?:the\s+)?differentiators?\s+to\s+(.+)/i',
            'content_goals' => '/\b(?:set|update|change)\s+(?:the\s+)?content\s+goals?\s+to\s+(.+)/i',
            'core_offer' => '/\b(?:set|update|change)\s+(?:the\s+)?core\s+offer\s+to\s+(.+)/i',
            'primary_growth_goal' => '/\b(?:set|update|change)\s+(?:the\s+)?(?:primary\s+)?growth\s+goal\s+to\s+(.+)/i',
            'known_blockers' => '/\b(?:set|update|change)\s+(?:the\s+)?(?:known\s+)?blockers?\s+to\s+(.+)/i',
        ];

        foreach ($fieldPatterns as $field => $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $value = trim((string) ($matches[1] ?? ''));
                $value = rtrim($value, ". \t\n\r\0\x0B");

                if ($value !== '') {
                    return [
                        'type' => 'company_field_update',
                        'field' => $field,
                        'value' => $value,
                        'summary' => 'update the shared company field "' . str_replace('_', ' ', $field) . '"',
                    ];
                }
            }
        }

        $platformActionPatterns = [
            [
                'platform' => 'atlas',
                'category' => 'campaign',
                'operation' => 'archive',
                'pattern' => '/\b(?:archive)\s+(?:the\s+)?campaign\s+"([^"]+)"/i',
                'target_from' => 1,
            ],
            [
                'platform' => 'atlas',
                'category' => 'campaign',
                'operation' => 'restore',
                'pattern' => '/\b(?:restore|unarchive)\s+(?:the\s+)?campaign\s+"([^"]+)"/i',
                'target_from' => 1,
            ],
            [
                'platform' => 'atlas',
                'category' => 'campaign',
                'operation' => 'duplicate',
                'pattern' => '/\b(?:duplicate|copy|clone)\s+(?:the\s+)?campaign\s+"([^"]+)"/i',
                'target_from' => 1,
            ],
        ];

        foreach ($platformActionPatterns as $meta) {
            if (preg_match($meta['pattern'], $message, $matches)) {
                $targetName = trim((string) ($matches[$meta['target_from']] ?? ''));
                if ($targetName === '') {
                    continue;
                }

                return [
                    'type' => 'platform_record_action',
                    'platform' => $meta['platform'],
                    'category' => $meta['category'],
                    'operation' => $meta['operation'],
                    'target_name' => $targetName,
                    'summary' => $meta['operation'] . ' the ' . $meta['category'] . ' "' . $targetName . '" in ' . ucfirst($meta['platform']),
                ];
            }
        }

        $platformUpdatePatterns = [
            [
                'platform' => 'bazaar',
                'category' => 'blog',
                'field' => 'description',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?bazaar\s+blog\s+"([^"]+)"\s+(?:description|content)\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'bazaar',
                'category' => 'blog',
                'field' => 'title',
                'pattern' => '/\b(?:update|change|rename|set)\s+(?:the\s+)?bazaar\s+blog\s+"([^"]+)"\s+(?:title|name)\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'servio',
                'category' => 'blog',
                'field' => 'description',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?servio\s+blog\s+"([^"]+)"\s+(?:description|content)\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'servio',
                'category' => 'blog',
                'field' => 'title',
                'pattern' => '/\b(?:update|change|rename|set)\s+(?:the\s+)?servio\s+blog\s+"([^"]+)"\s+(?:title|name)\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'atlas',
                'category' => 'campaign',
                'field' => 'description',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?campaign\s+"([^"]+)"\s+(?:description|brief|content)\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'atlas',
                'category' => 'campaign',
                'field' => 'title',
                'pattern' => '/\b(?:update|change|rename|set)\s+(?:the\s+)?campaign\s+"([^"]+)"\s+(?:title|name)\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'bazaar',
                'category' => 'page',
                'field' => 'content',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?bazaar\s+(about(?:\s+us)?|privacy(?:\s+policy)?|terms(?:\s+(?:and\s+)conditions?)?|refund(?:\s+policy)?)\s+page\s+(?:description|content)?\s*to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'servio',
                'category' => 'page',
                'field' => 'content',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?servio\s+(about(?:\s+us)?|privacy(?:\s+policy)?|terms(?:\s+(?:and\s+)conditions?)?|refund(?:\s+policy)?)\s+page\s+(?:description|content)?\s*to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'bazaar',
                'category' => 'product',
                'field' => 'description',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?product\s+"([^"]+)"\s+description\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'bazaar',
                'category' => 'product',
                'field' => 'title',
                'pattern' => '/\b(?:update|change|rename|set)\s+(?:the\s+)?product\s+"([^"]+)"\s+(?:title|name)\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'bazaar',
                'category' => 'product',
                'field' => 'price',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?product\s+"([^"]+)"\s+price\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'servio',
                'category' => 'service',
                'field' => 'description',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?service\s+"([^"]+)"\s+description\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'servio',
                'category' => 'service',
                'field' => 'title',
                'pattern' => '/\b(?:update|change|rename|set)\s+(?:the\s+)?service\s+"([^"]+)"\s+(?:title|name)\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'servio',
                'category' => 'service',
                'field' => 'price',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?service\s+"([^"]+)"\s+price\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'lms',
                'category' => 'task',
                'field' => 'description',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?task\s+"([^"]+)"\s+description\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'lms',
                'category' => 'task',
                'field' => 'title',
                'pattern' => '/\b(?:update|change|rename|set)\s+(?:the\s+)?task\s+"([^"]+)"\s+(?:title|name)\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'lms',
                'category' => 'task',
                'field' => 'status',
                'pattern' => '/\b(?:mark|set)\s+(?:the\s+)?task\s+"([^"]+)"\s+as\s+(complete|completed|done|pending|open)\b/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'lms',
                'category' => 'milestone',
                'field' => 'description',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?milestone\s+"([^"]+)"\s+description\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'lms',
                'category' => 'milestone',
                'field' => 'title',
                'pattern' => '/\b(?:update|change|rename|set)\s+(?:the\s+)?milestone\s+"([^"]+)"\s+(?:title|name)\s+to\s+(.+)/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'lms',
                'category' => 'milestone',
                'field' => 'status',
                'pattern' => '/\b(?:mark|set)\s+(?:the\s+)?milestone\s+"([^"]+)"\s+as\s+(complete|completed|done|pending|open)\b/i',
                'target_from' => 1,
                'value_from' => 2,
            ],
            [
                'platform' => 'bazaar',
                'category' => 'product',
                'field' => 'description',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?(?:latest|last)\s+product\s+description\s+to\s+(.+)/i',
            ],
            [
                'platform' => 'bazaar',
                'category' => 'product',
                'field' => 'title',
                'pattern' => '/\b(?:update|change|rename|set)\s+(?:the\s+)?(?:latest|last)\s+product\s+(?:title|name)\s+to\s+(.+)/i',
            ],
            [
                'platform' => 'bazaar',
                'category' => 'product',
                'field' => 'price',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?(?:latest|last)\s+product\s+price\s+to\s+(.+)/i',
            ],
            [
                'platform' => 'servio',
                'category' => 'service',
                'field' => 'description',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?(?:latest|last)\s+service\s+description\s+to\s+(.+)/i',
            ],
            [
                'platform' => 'servio',
                'category' => 'service',
                'field' => 'title',
                'pattern' => '/\b(?:update|change|rename|set)\s+(?:the\s+)?(?:latest|last)\s+service\s+(?:title|name)\s+to\s+(.+)/i',
            ],
            [
                'platform' => 'servio',
                'category' => 'service',
                'field' => 'price',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?(?:latest|last)\s+service\s+price\s+to\s+(.+)/i',
            ],
            [
                'platform' => 'lms',
                'category' => 'task',
                'field' => 'description',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?(?:latest|last)\s+task\s+description\s+to\s+(.+)/i',
            ],
            [
                'platform' => 'lms',
                'category' => 'task',
                'field' => 'title',
                'pattern' => '/\b(?:update|change|rename|set)\s+(?:the\s+)?(?:latest|last)\s+task\s+(?:title|name)\s+to\s+(.+)/i',
            ],
            [
                'platform' => 'lms',
                'category' => 'task',
                'field' => 'status',
                'pattern' => '/\b(?:mark|set)\s+(?:the\s+)?(?:latest|last)\s+task\s+as\s+(complete|completed|done|pending|open)\b/i',
            ],
            [
                'platform' => 'lms',
                'category' => 'milestone',
                'field' => 'description',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?(?:latest|last)\s+milestone\s+description\s+to\s+(.+)/i',
            ],
            [
                'platform' => 'lms',
                'category' => 'milestone',
                'field' => 'title',
                'pattern' => '/\b(?:update|change|rename|set)\s+(?:the\s+)?(?:latest|last)\s+milestone\s+(?:title|name)\s+to\s+(.+)/i',
            ],
            [
                'platform' => 'lms',
                'category' => 'milestone',
                'field' => 'status',
                'pattern' => '/\b(?:mark|set)\s+(?:the\s+)?(?:latest|last)\s+milestone\s+as\s+(complete|completed|done|pending|open)\b/i',
            ],
            [
                'platform' => 'bazaar',
                'category' => 'blog',
                'field' => 'description',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?(?:latest|last)\s+bazaar\s+blog\s+(?:description|content)\s+to\s+(.+)/i',
            ],
            [
                'platform' => 'bazaar',
                'category' => 'blog',
                'field' => 'title',
                'pattern' => '/\b(?:update|change|rename|set)\s+(?:the\s+)?(?:latest|last)\s+bazaar\s+blog\s+(?:title|name)\s+to\s+(.+)/i',
            ],
            [
                'platform' => 'servio',
                'category' => 'blog',
                'field' => 'description',
                'pattern' => '/\b(?:update|change|set)\s+(?:the\s+)?(?:latest|last)\s+servio\s+blog\s+(?:description|content)\s+to\s+(.+)/i',
            ],
            [
                'platform' => 'servio',
                'category' => 'blog',
                'field' => 'title',
                'pattern' => '/\b(?:update|change|rename|set)\s+(?:the\s+)?(?:latest|last)\s+servio\s+blog\s+(?:title|name)\s+to\s+(.+)/i',
            ],
        ];

        foreach ($platformUpdatePatterns as $meta) {
            if (preg_match($meta['pattern'], $message, $matches)) {
                $targetName = '';
                if (isset($meta['target_from'])) {
                    $targetName = trim((string) ($matches[$meta['target_from']] ?? ''));
                }

                $valueIndex = (int) ($meta['value_from'] ?? 1);
                $value = trim((string) ($matches[$valueIndex] ?? ''));
                $value = rtrim($value, ". \t\n\r\0\x0B");
                if ($value === '') {
                    continue;
                }

                $summary = $targetName !== ''
                    ? 'update the ' . $meta['category'] . ' "' . $targetName . '" ' . $meta['field'] . ' in ' . ucfirst($meta['platform'])
                    : 'update the latest ' . $meta['category'] . ' ' . $meta['field'] . ' in ' . ucfirst($meta['platform']);

                return [
                    'type' => 'platform_record_update',
                    'platform' => $meta['platform'],
                    'category' => $meta['category'],
                    'field' => $meta['field'],
                    'target_name' => $targetName,
                    'value' => $this->normalizePlatformUpdateValue($meta['field'], $value),
                    'summary' => $summary,
                ];
            }
        }

        $platformCreatePatterns = [
            [
                'platform' => 'bazaar',
                'category' => 'blog',
                'label' => 'Bazaar blog',
                'pattern' => '/\b(?:add|create|draft|write|prepare|make)\s+(?:a\s+)?bazaar\s+blog\s+"([^"]+)"(?:\s+(?:about|for|with)\s+(.+))?/i',
                'title_from' => 1,
                'description_from' => 2,
            ],
            [
                'platform' => 'servio',
                'category' => 'blog',
                'label' => 'Servio blog',
                'pattern' => '/\b(?:add|create|draft|write|prepare|make)\s+(?:a\s+)?servio\s+blog\s+"([^"]+)"(?:\s+(?:about|for|with)\s+(.+))?/i',
                'title_from' => 1,
                'description_from' => 2,
            ],
            [
                'platform' => 'atlas',
                'category' => 'campaign',
                'label' => 'Campaign brief',
                'pattern' => '/\b(?:add|create|draft|write|prepare|make)\s+(?:a\s+)?campaign\s+(?:brief\s+)?\"([^\"]+)\"(?:\s+(?:about|for|with)\s+(.+))?/i',
                'title_from' => 1,
                'description_from' => 2,
            ],
        ];

        foreach ($platformCreatePatterns as $meta) {
            if (preg_match($meta['pattern'], $message, $matches)) {
                $title = trim((string) ($matches[$meta['title_from']] ?? ''));
                if ($title === '') {
                    continue;
                }

                $description = trim((string) ($matches[$meta['description_from']] ?? ''));
                $description = rtrim($description, ". \t\n\r\0\x0B");

                return [
                    'type' => 'draft_record',
                    'platform' => $meta['platform'],
                    'category' => $meta['category'],
                    'title' => $title,
                    'request' => $description !== '' ? $description : $message,
                    'summary' => 'create a real ' . strtolower($meta['label']) . ' in Hatchers OS',
                ];
            }
        }

        if (!preg_match('/\b(add|create|draft|write|prepare|make)\b/i', $message)) {
            return [];
        }

        $categoryMap = [
            'product' => ['platform' => 'bazaar', 'label' => 'Product draft'],
            'service' => ['platform' => 'servio', 'label' => 'Service draft'],
            'blog' => ['platform' => 'bazaar', 'label' => 'Blog draft'],
            'page' => ['platform' => 'servio', 'label' => 'Page draft'],
            'campaign' => ['platform' => 'atlas', 'label' => 'Campaign brief'],
            'task' => ['platform' => 'lms', 'label' => 'Mentor task draft'],
            'milestone' => ['platform' => 'lms', 'label' => 'Milestone draft'],
        ];

        foreach ($categoryMap as $keyword => $meta) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . 's?\b/i', $message)) {
                return [
                    'type' => 'draft_record',
                    'platform' => $meta['platform'],
                    'category' => $keyword,
                    'title' => $this->makeDraftTitle($meta['label'], $message),
                    'request' => $message,
                    'summary' => 'create a ' . strtolower($meta['label']) . ' in the founder operating plan',
                ];
            }
        }

        return [];
    }

    private function makeDraftTitle(string $prefix, string $message): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $message) ?? '');
        if ($clean === '') {
            return $prefix;
        }

        return Str::limit($prefix . ': ' . $clean, 110, '');
    }

    private function detectActorRoleFromText(string $message): string
    {
        $message = strtolower(trim($message));
        if ($message === '') {
            return '';
        }

        if (preg_match('/\bmentor\b/', $message)) {
            return 'mentor';
        }

        if (preg_match('/\bfounder\b/', $message)) {
            return 'founder';
        }

        return '';
    }

    private function isConfirmationMessage(string $message): bool
    {
        $message = strtolower(trim($message));
        if ($message === '') {
            return false;
        }

        foreach (['yes', 'confirm', 'confirmed', 'proceed', 'go ahead', 'do it', 'continue', 'approved'] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isRejectionMessage(string $message): bool
    {
        $message = strtolower(trim($message));
        if ($message === '') {
            return false;
        }

        foreach (['cancel', 'stop', 'never mind', 'dont', "don't", 'no '] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return $message === 'no';
    }

    private function getPendingAction(Request $request, Founder $founder): array
    {
        return (array) $request->session()->get($this->sessionKey($founder), []);
    }

    private function setPendingAction(Request $request, Founder $founder, array $action): void
    {
        $request->session()->put($this->sessionKey($founder), $action);
    }

    private function clearPendingAction(Request $request, Founder $founder): void
    {
        $request->session()->forget($this->sessionKey($founder));
    }

    private function sessionKey(Founder $founder): string
    {
        return self::SESSION_KEY_PREFIX . $founder->id;
    }

    private function ctaLabelForPlatform(string $platform): string
    {
        return match ($platform) {
            'bazaar' => 'Open Bazaar',
            'servio' => 'Open Servio',
            'lms' => 'Open LMS',
            'atlas' => 'Open Atlas',
            default => 'Open OS',
        };
    }

    private function ctaUrlForPlatform(string $platform): string
    {
        return match ($platform) {
            'bazaar' => 'https://bazaar.hatchers.ai/admin/dashboard',
            'servio' => 'https://servio.hatchers.ai/admin/dashboard',
            'lms' => 'https://lms.hatchers.ai/',
            'atlas' => 'https://atlas.hatchers.ai/dashboard',
            default => '/dashboard',
        };
    }

    private function handleReadOnlyQuery(Founder $founder, string $message): array
    {
        $normalized = strtolower(trim($message));
        if ($normalized === '') {
            return [];
        }

        $atlasSnapshot = $founder->moduleSnapshots->keyBy('module')->get('atlas');
        $payload = $atlasSnapshot?->payload_json ?? [];
        $summary = $payload['summary'] ?? [];
        $active = is_array($summary['recent_campaigns'] ?? null) ? $summary['recent_campaigns'] : [];
        $archived = is_array($summary['archived_campaigns'] ?? null) ? $summary['archived_campaigns'] : [];

        if (preg_match('/\bopen\s+campaign\s+"([^"]+)"/i', $message, $matches)) {
            $targetName = trim((string) ($matches[1] ?? ''));
            $campaign = $this->findCampaignInSnapshot(array_merge($active, $archived), $targetName);
            if (empty($campaign)) {
                return [
                    'reply' => 'I could not find that campaign in the current OS snapshot. Try the exact campaign name as shown in Atlas.',
                    'actions' => [],
                ];
            }

            return [
                'reply' => 'Here is the Atlas campaign workspace for "' . ($campaign['title'] ?? $targetName) . '".',
                'actions' => !empty($campaign['url']) ? [[
                    'title' => (string) ($campaign['title'] ?? $targetName),
                    'platform' => 'atlas',
                    'url' => (string) $campaign['url'],
                ]] : [],
            ];
        }

        if (preg_match('/\bactive\s+campaigns?\b|\bshow\s+me\s+active\b|\blist\s+active\b/i', $normalized)) {
            if (empty($active)) {
                return [
                    'reply' => 'There are no active Atlas campaigns in the current OS snapshot yet.',
                    'actions' => [],
                ];
            }

            return $this->formatCampaignListReply(
                'Here are the active Atlas campaigns I can see from Hatchers OS:',
                $active
            );
        }

        if (preg_match('/\b(?:summari[sz]e|summary of)\s+(?:our\s+)?active\s+campaigns\b|\bwhat\s+are\s+our\s+active\s+campaigns\b/i', $normalized)) {
            if (empty($active)) {
                return [
                    'reply' => 'There are no active Atlas campaigns in the current OS snapshot yet.',
                    'actions' => [],
                ];
            }

            $lines = [];
            $actions = [];
            foreach (array_slice($active, 0, 5) as $campaign) {
                $title = trim((string) ($campaign['title'] ?? 'Campaign'));
                $description = trim((string) ($campaign['description'] ?? ''));
                $postCount = (int) ($campaign['generated_posts_count'] ?? 0);
                $lastGenerated = trim((string) ($campaign['last_generated_at'] ?? ''));

                $line = '- ' . $title;
                if ($description !== '') {
                    $line .= ': ' . $description;
                }
                if ($postCount > 0) {
                    $line .= ' · ' . $postCount . ' linked posts';
                }
                if ($lastGenerated !== '') {
                    $line .= ' · Last generated ' . $lastGenerated;
                }
                $lines[] = $line;

                if (!empty($campaign['url'])) {
                    $actions[] = [
                        'title' => $title,
                        'platform' => 'atlas',
                        'url' => (string) $campaign['url'],
                    ];
                }
            }

            return [
                'reply' => "Here is a quick summary of your active Atlas campaigns:\n" . implode("\n", $lines),
                'actions' => $actions,
            ];
        }

        if (preg_match('/\b(?:which|what)\s+campaign\s+(?:has|has the)\s+(?:most|highest)\s+(?:linked\s+posts|posts)\b/i', $normalized)) {
            if (empty($active)) {
                return [
                    'reply' => 'There are no active Atlas campaigns in the current OS snapshot yet.',
                    'actions' => [],
                ];
            }

            $topCampaign = $this->campaignWithMostLinkedPosts($active);
            if (empty($topCampaign)) {
                return [
                    'reply' => 'I could not determine a top campaign from the current OS snapshot.',
                    'actions' => [],
                ];
            }

            $title = trim((string) ($topCampaign['title'] ?? 'Campaign'));
            $postCount = (int) ($topCampaign['generated_posts_count'] ?? 0);
            $reply = $title . ' currently has the most linked posts in Atlas with ' . $postCount . '.';
            if (!empty($topCampaign['last_generated_at'])) {
                $reply .= ' It was last generated at ' . $topCampaign['last_generated_at'] . '.';
            }

            return [
                'reply' => $reply,
                'actions' => !empty($topCampaign['url']) ? [[
                    'title' => $title,
                    'platform' => 'atlas',
                    'url' => (string) $topCampaign['url'],
                ]] : [],
            ];
        }

        if (preg_match('/\b(?:which|what)\s+campaigns?\s+(?:were|was)\s+(?:generated|updated)\s+(?:most\s+)?recently\b|\bmost\s+recent(?:ly)?\s+generated\s+campaigns?\b/i', $normalized)) {
            if (empty($active)) {
                return [
                    'reply' => 'There are no active Atlas campaigns in the current OS snapshot yet.',
                    'actions' => [],
                ];
            }

            $sorted = $this->sortCampaignsByRecentGeneration($active);
            return $this->formatCampaignListReply(
                'Here are the most recently generated Atlas campaigns I can see from Hatchers OS:',
                array_slice($sorted, 0, 5)
            );
        }

        if (preg_match('/\barchived\s+campaigns?\b|\bshow\s+me\s+archived\b|\blist\s+archived\b/i', $normalized)) {
            if (empty($archived)) {
                return [
                    'reply' => 'There are no archived Atlas campaigns in the current OS snapshot. Once a campaign is archived in Atlas, it will appear here.',
                    'actions' => [],
                ];
            }

            return $this->formatCampaignListReply(
                'Here are the archived Atlas campaigns I can see from Hatchers OS:',
                $archived
            );
        }

        return [];
    }

    private function createPlatformRecord(Founder $founder, array $action): array
    {
        $platform = trim((string) ($action['platform'] ?? ''));
        $category = trim((string) ($action['category'] ?? ''));
        $sharedSecret = trim((string) config('services.os.shared_secret'));

        $baseUrl = match ($platform) {
            'atlas' => rtrim((string) config('services.atlas.base_url'), '/'),
            'bazaar' => rtrim((string) config('services.bazaar.base_url'), '/'),
            'servio' => rtrim((string) config('services.servio.base_url'), '/'),
            'lms' => rtrim((string) config('services.lms.base_url'), '/'),
            default => '',
        };

        $path = match ($platform) {
            'atlas' => '/hatchers/intelligence/actions',
            'bazaar', 'servio' => '/api/hatchers/action',
            'lms' => '/hatchers/action',
            default => '',
        };

        if ($baseUrl === '' || $path === '' || $sharedSecret === '') {
            return [
                'success' => false,
                'reply' => 'This platform action is not configured yet in Hatchers OS.',
            ];
        }

        $payload = [
            'username' => $founder->username,
            'email' => $founder->email,
            'category' => $category,
            'title' => (string) ($action['title'] ?? ''),
            'description' => (string) ($action['description'] ?? ''),
            'actor_role' => (string) ($action['actor_role'] ?? 'founder'),
        ];

        if (!empty($action['payload']) && is_array($action['payload'])) {
            $payload = array_merge($payload, $action['payload']);
        }

        $json = json_encode($payload);
        if ($json === false) {
            return [
                'success' => false,
                'reply' => 'I could not prepare that platform action payload.',
            ];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Hatchers-Signature' => hash_hmac('sha256', $json, $sharedSecret),
                    'Content-Type' => 'application/json',
                ])
                ->withBody($json, 'application/json')
                ->post($baseUrl . $path);
        } catch (\Throwable $exception) {
            Log::warning('OS platform action request failed', [
                'platform' => $platform,
                'founder_id' => $founder->id,
                'message' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'reply' => 'I could not reach ' . ucfirst($platform) . ' to create that ' . $category . ' right now.',
            ];
        }

        if (!$response->successful()) {
            return [
                'success' => false,
                'reply' => (string) ($response->json('error') ?: ('I could not create that ' . $category . ' in ' . ucfirst($platform) . '.')),
            ];
        }

        return [
            'success' => true,
            'title' => (string) ($response->json('title') ?: ($action['title'] ?? '')),
            'edit_url' => (string) ($response->json('edit_url') ?: $this->ctaUrlForPlatform($platform)),
            'reply' => 'Done. I created that ' . $category . ' in ' . ucfirst($platform) . ' and linked it back into Hatchers OS.',
        ];
    }

    private function updateCommerceFieldFromOs(
        Founder $founder,
        string $platform,
        string $category,
        string $title,
        string $field,
        string $value,
        string $actorRole
    ): array {
        $result = $this->updatePlatformRecord($founder, [
            'platform' => $platform,
            'category' => $category,
            'field' => $field,
            'value' => $value,
            'target_name' => trim($title),
            'actor_role' => $actorRole,
        ]);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        return [
            'success' => true,
            'reply' => 'Done. I updated that ' . $category . ' ' . $field . ' in ' . ucfirst($platform) . ' from Hatchers OS.',
            'action_type' => 'platform_record_update',
            'sync_summary' => 'Hatchers OS updated a ' . $category . ' field in ' . ucfirst($platform) . '.',
        ];
    }

    private function updatePlatformRecord(Founder $founder, array $action): array
    {
        $platform = trim((string) ($action['platform'] ?? ''));
        $category = trim((string) ($action['category'] ?? ''));
        $sharedSecret = trim((string) config('services.os.shared_secret'));

        $baseUrl = match ($platform) {
            'atlas' => rtrim((string) config('services.atlas.base_url'), '/'),
            'bazaar' => rtrim((string) config('services.bazaar.base_url'), '/'),
            'servio' => rtrim((string) config('services.servio.base_url'), '/'),
            'lms' => rtrim((string) config('services.lms.base_url'), '/'),
            default => '',
        };

        $path = match ($platform) {
            'atlas' => '/hatchers/intelligence/actions',
            'bazaar', 'servio' => '/api/hatchers/action',
            'lms' => '/hatchers/action',
            default => '',
        };

        if ($baseUrl === '' || $path === '' || $sharedSecret === '') {
            return [
                'success' => false,
                'reply' => 'This platform update is not configured yet in Hatchers OS.',
            ];
        }

        $payload = [
            'operation' => 'update',
            'username' => $founder->username,
            'email' => $founder->email,
            'category' => $category,
            'field' => (string) ($action['field'] ?? ''),
            'value' => (string) ($action['value'] ?? ''),
            'actor_role' => (string) ($action['actor_role'] ?? 'founder'),
            'target' => !empty($action['target_name']) ? 'named' : 'latest',
            'target_name' => (string) ($action['target_name'] ?? ''),
        ];

        if (!empty($action['payload']) && is_array($action['payload'])) {
            $payload = array_merge($payload, $action['payload']);
        }

        $json = json_encode($payload);
        if ($json === false) {
            return [
                'success' => false,
                'reply' => 'I could not prepare that platform update payload.',
            ];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Hatchers-Signature' => hash_hmac('sha256', $json, $sharedSecret),
                    'Content-Type' => 'application/json',
                ])
                ->withBody($json, 'application/json')
                ->post($baseUrl . $path);
        } catch (\Throwable $exception) {
            Log::warning('OS platform update request failed', [
                'platform' => $platform,
                'founder_id' => $founder->id,
                'message' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'reply' => 'I could not reach ' . ucfirst($platform) . ' to update that ' . $category . ' right now.',
            ];
        }

        if (!$response->successful()) {
            return [
                'success' => false,
                'reply' => (string) ($response->json('error') ?: ('I could not update that ' . $category . ' in ' . ucfirst($platform) . '.')),
            ];
        }

        return [
            'success' => true,
            'title' => (string) ($response->json('title') ?: ('Updated ' . $category)),
            'edit_url' => (string) ($response->json('edit_url') ?: $this->ctaUrlForPlatform($platform)),
            'reply' => 'Done. I updated ' . (!empty($action['target_name']) ? 'the ' . $category . ' "' . $action['target_name'] . '"' : 'the latest ' . $category) . ' in ' . ucfirst($platform) . ' and linked it back into Hatchers OS.',
        ];
    }

    private function actOnPlatformRecord(Founder $founder, array $action): array
    {
        $platform = trim((string) ($action['platform'] ?? ''));
        $category = trim((string) ($action['category'] ?? ''));
        $operation = trim((string) ($action['operation'] ?? ''));
        $sharedSecret = trim((string) config('services.os.shared_secret'));

        $baseUrl = match ($platform) {
            'atlas' => rtrim((string) config('services.atlas.base_url'), '/'),
            default => '',
        };

        $path = match ($platform) {
            'atlas' => '/hatchers/intelligence/actions',
            default => '',
        };

        if ($baseUrl === '' || $path === '' || $sharedSecret === '') {
            return [
                'success' => false,
                'reply' => 'This platform action is not configured yet in Hatchers OS.',
            ];
        }

        $campaignId = '';
        if ($platform === 'atlas' && $category === 'campaign' && !empty($action['target_name'])) {
            $atlasSnapshot = $founder->moduleSnapshots->keyBy('module')->get('atlas');
            $payload = $atlasSnapshot?->payload_json ?? [];
            $summary = $payload['summary'] ?? [];
            $allCampaigns = array_merge(
                is_array($summary['recent_campaigns'] ?? null) ? $summary['recent_campaigns'] : [],
                is_array($summary['archived_campaigns'] ?? null) ? $summary['archived_campaigns'] : []
            );

            foreach ($allCampaigns as $campaign) {
                if (strcasecmp(trim((string) ($campaign['title'] ?? '')), trim((string) $action['target_name'])) === 0) {
                    $campaignId = trim((string) ($campaign['id'] ?? ''));
                    break;
                }
            }
        }

        if ($campaignId === '') {
            return [
                'success' => false,
                'reply' => 'I could not find that campaign in the current OS snapshot. Try the exact campaign name as shown in Atlas.',
            ];
        }

        $payload = [
            'operation' => $operation,
            'username' => $founder->username,
            'email' => $founder->email,
            'category' => $category,
            'campaign_id' => $campaignId,
            'actor_role' => (string) ($action['actor_role'] ?? 'founder'),
        ];

        $json = json_encode($payload);
        if ($json === false) {
            return [
                'success' => false,
                'reply' => 'I could not prepare that platform action payload.',
            ];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Hatchers-Signature' => hash_hmac('sha256', $json, $sharedSecret),
                    'Content-Type' => 'application/json',
                ])
                ->withBody($json, 'application/json')
                ->post($baseUrl . $path);
        } catch (\Throwable $exception) {
            Log::warning('OS platform action request failed', [
                'platform' => $platform,
                'founder_id' => $founder->id,
                'message' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'reply' => 'I could not reach ' . ucfirst($platform) . ' to ' . $operation . ' that ' . $category . ' right now.',
            ];
        }

        if (!$response->successful()) {
            return [
                'success' => false,
                'reply' => (string) ($response->json('error') ?: ('I could not ' . $operation . ' that ' . $category . ' in ' . ucfirst($platform) . '.')),
            ];
        }

        return [
            'success' => true,
            'title' => (string) ($response->json('title') ?: (ucfirst($this->operationPastTense($operation)) . ' ' . $category)),
            'edit_url' => (string) ($response->json('edit_url') ?: $this->ctaUrlForPlatform($platform)),
            'reply' => 'Done. I ' . $this->operationPastTense($operation) . ' the ' . $category . ' "' . ($action['target_name'] ?? '') . '" in ' . ucfirst($platform) . ' and linked it back into Hatchers OS.',
        ];
    }

    private function formatCampaignListReply(string $intro, array $campaigns): array
    {
        $lines = [];
        $actions = [];
        foreach (array_slice($campaigns, 0, 5) as $campaign) {
            $title = trim((string) ($campaign['title'] ?? 'Campaign'));
            $updatedAt = trim((string) ($campaign['updated_at'] ?? ''));
            $postCount = (int) ($campaign['generated_posts_count'] ?? 0);
            $lines[] = '- ' . $title . ($postCount > 0 ? ' (' . $postCount . ' linked posts)' : '') . ($updatedAt !== '' ? ' · ' . $updatedAt : '');

            if (!empty($campaign['url'])) {
                $actions[] = [
                    'title' => $title,
                    'platform' => 'atlas',
                    'url' => (string) $campaign['url'],
                ];
            }
        }

        return [
            'reply' => $intro . "\n" . implode("\n", $lines),
            'actions' => $actions,
        ];
    }

    private function handleCampaignStateAction(Founder $founder, string $title, string $operation, string $actorRole): array
    {
        $title = trim($title);
        if ($title === '') {
            return [
                'success' => false,
                'reply' => 'Please choose a campaign before trying to ' . $operation . ' it.',
            ];
        }

        $result = $this->actOnPlatformRecord($founder, [
            'platform' => 'atlas',
            'category' => 'campaign',
            'operation' => $operation,
            'target_name' => $title,
            'actor_role' => $actorRole,
        ]);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        $actionLabel = $operation === 'archive' ? 'Archived' : 'Restored';
        $this->recordActionPlan(
            $founder,
            $actionLabel . ' campaign',
            'Atlas ' . strtolower($actionLabel) . ' campaign "' . $title . '" from Hatchers OS.',
            'atlas',
            $result['edit_url'] ?? $this->ctaUrlForPlatform('atlas'),
            $this->ctaLabelForPlatform('atlas'),
            $operation === 'archive' ? 'archived' : 'updated'
        );

        return [
            'success' => true,
            'title' => $title,
            'edit_url' => (string) ($result['edit_url'] ?? ''),
            'reply' => $result['reply'] ?? ('Done. I ' . $this->operationPastTense($operation) . ' that campaign in Atlas and linked it back into Hatchers OS.'),
            'action_type' => 'platform_record_action',
            'sync_summary' => 'Atlas ' . $this->operationPastTense($operation) . ' "' . $title . '" in atlas from Hatchers OS.',
        ];
    }

    private function findCampaignInSnapshot(array $campaigns, string $targetName): array
    {
        $target = trim(mb_strtolower($targetName));
        if ($target === '') {
            return [];
        }

        foreach ($campaigns as $campaign) {
            $title = trim(mb_strtolower((string) ($campaign['title'] ?? '')));
            if ($title === $target) {
                return is_array($campaign) ? $campaign : [];
            }
        }

        return [];
    }

    private function campaignWithMostLinkedPosts(array $campaigns): array
    {
        usort($campaigns, function ($left, $right) {
            $leftPosts = (int) ($left['generated_posts_count'] ?? 0);
            $rightPosts = (int) ($right['generated_posts_count'] ?? 0);

            if ($leftPosts === $rightPosts) {
                return strtotime((string) ($right['updated_at'] ?? '')) <=> strtotime((string) ($left['updated_at'] ?? ''));
            }

            return $rightPosts <=> $leftPosts;
        });

        return is_array($campaigns[0] ?? null) ? $campaigns[0] : [];
    }

    private function sortCampaignsByRecentGeneration(array $campaigns): array
    {
        usort($campaigns, function ($left, $right) {
            $leftTime = strtotime((string) ($left['last_generated_at'] ?? $left['updated_at'] ?? ''));
            $rightTime = strtotime((string) ($right['last_generated_at'] ?? $right['updated_at'] ?? ''));
            return $rightTime <=> $leftTime;
        });

        return $campaigns;
    }

    private function recordActionPlan(
        Founder $founder,
        string $title,
        string $description,
        string $platform,
        string $ctaUrl,
        string $ctaLabel,
        string $status = 'pending'
    ): void {
        FounderActionPlan::create([
            'founder_id' => $founder->id,
            'title' => $title,
            'description' => $description,
            'platform' => $platform,
            'priority' => 72,
            'status' => $status,
            'cta_label' => $ctaLabel,
            'cta_url' => $ctaUrl,
        ]);
    }

    private function normalizePlatformUpdateValue(string $field, string $value): string
    {
        if ($field !== 'status') {
            return $value;
        }

        $normalized = strtolower($value);
        if (in_array($normalized, ['complete', 'completed', 'done'], true)) {
            return 'completed';
        }

        return 'pending';
    }

    private function operationPastTense(string $operation): string
    {
        return match (strtolower(trim($operation))) {
            'archive' => 'archived',
            'restore' => 'restored',
            default => trim($operation) . 'd',
        };
    }
}
