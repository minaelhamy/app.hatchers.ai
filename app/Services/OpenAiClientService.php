<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiClientService
{
    public function hasApiKey(): bool
    {
        return trim((string) config('services.openai.api_key', '')) !== '';
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
    }

    public function model(string $configKey = 'chat_model', string $fallback = 'gpt-5.5'): string
    {
        return trim((string) config('services.openai.' . $configKey, $fallback));
    }

    public function modelCandidates(string $configKey = 'chat_model', string $fallback = 'gpt-5.5'): array
    {
        $candidates = [
            $this->model($configKey, $fallback),
            trim((string) config('services.openai.fallback_model', 'gpt-5.2')),
            trim((string) config('services.openai.legacy_fallback_model', 'gpt-4.1-mini')),
        ];

        return array_values(array_filter(array_unique(array_map(
            static fn ($model) => trim((string) $model),
            $candidates
        ))));
    }

    public function requestJsonObject(
        string $systemPrompt,
        string $userPrompt,
        string $configKey = 'chat_model',
        string $fallbackModel = 'gpt-5.5',
        ?float $temperature = null,
        int $timeoutSeconds = 45
    ): array {
        if (!$this->hasApiKey()) {
            return [];
        }

        $preferredModel = $this->model($configKey, $fallbackModel);

        foreach ($this->modelCandidates($configKey, $fallbackModel) as $model) {
            try {
                $payload = $this->buildPayload($model, $systemPrompt, $userPrompt, $temperature);
                $response = $this->sendRequest($payload, $timeoutSeconds);

                if (!$response->successful() && $this->shouldRetryWithoutTemperature($response, $payload)) {
                    unset($payload['temperature']);
                    $response = $this->sendRequest($payload, $timeoutSeconds);
                }

                if (!$response->successful()) {
                    $this->logFailure($response, $configKey, $model);
                    if ($this->shouldTryFallback($response)) {
                        continue;
                    }

                    return [];
                }

                $content = trim((string) data_get($response->json(), 'choices.0.message.content', ''));
                if ($content === '') {
                    return [];
                }

                $decoded = json_decode($content, true);
                if (!is_array($decoded)) {
                    return [];
                }

                if ($model !== $preferredModel) {
                    Log::info('OpenAI request succeeded on fallback model.', [
                        'model_config' => $configKey,
                        'model' => $model,
                    ]);
                }

                return $decoded;
            } catch (\Throwable $exception) {
                Log::warning('OpenAI request threw an exception.', [
                    'model_config' => $configKey,
                    'model' => $model,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return [];
    }

    private function buildPayload(string $model, string $systemPrompt, string $userPrompt, ?float $temperature): array
    {
        $payload = [
            'model' => $model,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        if ($temperature !== null) {
            $payload['temperature'] = $temperature;
        }

        return $payload;
    }

    private function sendRequest(array $payload, int $timeoutSeconds): Response
    {
        return Http::withToken((string) config('services.openai.api_key', ''))
            ->acceptJson()
            ->timeout($timeoutSeconds)
            ->post($this->baseUrl() . '/chat/completions', $payload);
    }

    private function shouldTryFallback(Response $response): bool
    {
        $body = strtolower((string) $response->body());

        if (str_contains($body, 'does not have access to model')) {
            return true;
        }

        if (str_contains($body, 'model') && str_contains($body, 'not found')) {
            return true;
        }

        if (str_contains($body, 'unsupported model')) {
            return true;
        }

        return in_array($response->status(), [400, 403, 404], true);
    }

    private function shouldRetryWithoutTemperature(Response $response, array $payload): bool
    {
        if (!array_key_exists('temperature', $payload)) {
            return false;
        }

        $body = strtolower((string) $response->body());

        return str_contains($body, "unsupported parameter: 'temperature'")
            || (str_contains($body, 'temperature') && str_contains($body, 'not supported'));
    }

    private function logFailure(Response $response, string $configKey, string $model): void
    {
        Log::warning('OpenAI request failed.', [
            'model_config' => $configKey,
            'model' => $model,
            'status' => $response->status(),
            'body' => substr((string) $response->body(), 0, 1200),
        ]);
    }
}
