<?php

namespace App\Services\AI\Contracts;

use App\Models\ContentBrief;
use App\Models\Project;
use App\Services\AI\ScriptResult;

interface ScriptProvider
{
    /**
     * Machine-readable provider identifier (e.g. "mock", "gemini").
     */
    public function name(): string;

    /**
     * The underlying model identifier, or null when not applicable.
     */
    public function model(): ?string;

    /**
     * Generate a raw script payload for the given project and brief.
     *
     * Implementations must return an array shaped like:
     * [
     *     'title' => string,
     *     'hook' => ?string,
     *     'closing_cta' => ?string,
     *     'scenes' => list<array{
     *         scene_number: int,
     *         narration: string,
     *         visual_direction: ?string,
     *         estimated_duration_seconds: ?int,
     *     }>,
     * ]
     *
     * The payload is validated and normalized by {@see ScriptResult}.
     *
     * @param  array{max_scenes?: int, include_closing?: bool}  $options
     * @return array<string, mixed>
     */
    public function generate(Project $project, ContentBrief $brief, array $options = []): array;
}
