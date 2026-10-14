<?php

namespace App\Services\AI\Providers;

use App\Models\ContentBrief;
use App\Models\Project;
use App\Services\AI\Contracts\ScriptProvider;
use Illuminate\Support\Str;

/**
 * Deterministic, offline provider used for local development and tests.
 */
class MockScriptProvider implements ScriptProvider
{
    public function name(): string
    {
        return 'mock';
    }

    public function model(): ?string
    {
        return null;
    }

    public function generate(Project $project, ContentBrief $brief, array $options = []): array
    {
        $targetDuration = (int) $project->target_duration_seconds;
        $targetDuration = $targetDuration > 0 ? $targetDuration : 30;

        $sceneCount = isset($options['max_scenes'])
            ? max(1, min(10, (int) $options['max_scenes']))
            : max(2, min(5, (int) ceil($targetDuration / 6)));

        $baseDuration = intdiv($targetDuration, $sceneCount);
        $remainder = $targetDuration % $sceneCount;

        $scenes = [];
        for ($i = 1; $i <= $sceneCount; $i++) {
            $duration = $baseDuration + ($i <= $remainder ? 1 : 0);

            $scenes[] = [
                'scene_number' => $i,
                'narration' => "Scene {$i}: {$brief->topic} - engaging hook/narration.",
                'visual_direction' => "Show relevant visuals for {$brief->topic}.",
                'estimated_duration_seconds' => $duration,
            ];
        }

        $includeClosing = (bool) ($options['include_closing'] ?? true);

        return [
            'title' => Str::limit($brief->topic, 160, ''),
            'hook' => "Hook: {$brief->topic}",
            'closing_cta' => $includeClosing ? 'Follow for more!' : null,
            'scenes' => $scenes,
        ];
    }
}
