<?php

namespace App\Services\AI\Providers;

use App\Models\ContentBrief;
use App\Models\Project;
use App\Services\AI\Contracts\ScriptProvider;
use App\Services\AI\Exceptions\InvalidProviderOutputException;
use App\Services\AI\Exceptions\ProviderConfigurationException;
use App\Services\AI\Exceptions\ProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Google Gemini provider using the REST `generateContent` endpoint with
 * structured JSON output. No SDK dependency; uses Laravel's HTTP client.
 *
 * @see https://ai.google.dev/api/generate-content
 * @see https://ai.google.dev/gemini-api/docs/structured-output
 */
class GeminiScriptProvider implements ScriptProvider
{
    public function name(): string
    {
        return 'gemini';
    }

    public function model(): ?string
    {
        $model = config('services.ai.gemini.model');

        return is_string($model) && trim($model) !== '' ? $model : null;
    }

    public function generate(Project $project, ContentBrief $brief, array $options = []): array
    {
        $apiKey = config('services.ai.gemini.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new ProviderConfigurationException('Gemini API key is not configured.');
        }

        $model = $this->model();

        if ($model === null) {
            throw new ProviderConfigurationException('Gemini model is not configured.');
        }

        $baseUrl = rtrim((string) config('services.ai.gemini.base_url'), '/');

        if ($baseUrl === '') {
            throw new ProviderConfigurationException('Gemini base URL is not configured.');
        }

        $timeout = (int) config('services.ai.timeout', 30);
        $timeout = $timeout > 0 ? $timeout : 30;

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $this->systemInstruction()]],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $this->userPrompt($project, $brief, $options)]],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $this->responseSchema(),
                'temperature' => 0.7,
            ],
        ];

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
                'Accept' => 'application/json',
            ])
                ->connectTimeout(min(10, $timeout))
                ->timeout($timeout)
                ->post("{$baseUrl}/models/{$model}:generateContent", $payload);
        } catch (ConnectionException $e) {
            throw new ProviderException('Unable to reach the Gemini API.', 0, $e);
        }

        if ($response->failed()) {
            throw new ProviderException(
                'Gemini API request failed with status '.$response->status().'.'
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new InvalidProviderOutputException('Gemini API returned an invalid response body.');
        }

        return $this->extractScript($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function extractScript(array $data): array
    {
        $blockReason = data_get($data, 'promptFeedback.blockReason');

        if (is_string($blockReason) && $blockReason !== '') {
            throw new InvalidProviderOutputException("Gemini blocked the prompt ({$blockReason}).");
        }

        $finishReason = data_get($data, 'candidates.0.finishReason');

        if ($finishReason === 'MAX_TOKENS') {
            throw new InvalidProviderOutputException('Gemini response was truncated (MAX_TOKENS).');
        }

        $text = data_get($data, 'candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw new InvalidProviderOutputException('Gemini response contained no script content.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new InvalidProviderOutputException('Gemini response was not valid JSON.');
        }

        return $decoded;
    }

    private function systemInstruction(): string
    {
        return 'You are an expert short-form video scriptwriter. '
            .'Return only a single JSON object that matches the provided schema. '
            .'Write concise, spoken narration and clear, actionable visual direction.';
    }

    /**
     * @param  array{max_scenes?: int, include_closing?: bool}  $options
     */
    private function userPrompt(Project $project, ContentBrief $brief, array $options): string
    {
        $maxScenes = max(1, min(10, (int) ($options['max_scenes'] ?? 5)));
        $includeClosing = (bool) ($options['include_closing'] ?? true);

        $lines = [
            'Write a short-form video script using the details below.',
            '',
            'Project:',
            '- Platform: '.($project->platform ?? 'unspecified'),
            '- Aspect ratio: '.($project->aspect_ratio ?? 'unspecified'),
            '- Target duration: '.((int) $project->target_duration_seconds).' seconds',
            '',
            'Content brief:',
            '- Topic: '.($brief->topic ?? 'unspecified'),
            '- Category: '.($brief->category ?? 'unspecified'),
            '- Content goal: '.($brief->content_goal ?? 'unspecified'),
            '- Target audience: '.($brief->target_audience ?? 'unspecified'),
            '- Tone: '.($brief->tone ?? 'unspecified'),
        ];

        if (is_string($brief->additional_instructions) && trim($brief->additional_instructions) !== '') {
            $lines[] = '- Additional instructions: '.$brief->additional_instructions;
        }

        $lines[] = '';
        $lines[] = 'Requirements:';
        $lines[] = "- Produce between 1 and {$maxScenes} scenes.";
        $lines[] = '- Each scene needs narration and visual direction.';
        $lines[] = '- estimated_duration_seconds must be a positive integer.';
        $lines[] = $includeClosing
            ? '- Include a closing call to action in closing_cta.'
            : '- Set closing_cta to null; do not include a call to action.';

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    private function responseSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'hook' => ['type' => 'STRING'],
                'closing_cta' => ['type' => 'STRING', 'nullable' => true],
                'scenes' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'scene_number' => ['type' => 'INTEGER'],
                            'narration' => ['type' => 'STRING'],
                            'visual_direction' => ['type' => 'STRING'],
                            'estimated_duration_seconds' => ['type' => 'INTEGER'],
                        ],
                        'required' => [
                            'scene_number',
                            'narration',
                            'visual_direction',
                            'estimated_duration_seconds',
                        ],
                    ],
                ],
            ],
            'required' => ['title', 'hook', 'closing_cta', 'scenes'],
        ];
    }
}
