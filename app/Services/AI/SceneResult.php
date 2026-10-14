<?php

namespace App\Services\AI;

/**
 * Validated, normalized representation of a single script scene.
 */
final class SceneResult
{
    public function __construct(
        public readonly int $sceneNumber,
        public readonly string $narration,
        public readonly ?string $visualDirection,
        public readonly ?int $estimatedDurationSeconds,
    ) {}

    /**
     * @return array{scene_number: int, narration: string, visual_direction: ?string, estimated_duration_seconds: ?int}
     */
    public function toArray(): array
    {
        return [
            'scene_number' => $this->sceneNumber,
            'narration' => $this->narration,
            'visual_direction' => $this->visualDirection,
            'estimated_duration_seconds' => $this->estimatedDurationSeconds,
        ];
    }
}
