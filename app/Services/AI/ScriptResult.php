<?php

namespace App\Services\AI;

use App\Services\AI\Exceptions\InvalidProviderOutputException;
use Illuminate\Support\Arr;

/**
 * Validated, normalized representation of a generated script.
 *
 * This is the single boundary that turns arbitrary provider output into a
 * predictable shape. Any provider (mock, Gemini, future) must pass through
 * {@see self::fromArray()} before its output is persisted.
 */
final class ScriptResult
{
    public const MIN_SCENES = 1;

    public const MAX_SCENES = 10;

    /**
     * Matches the `title` column length on script_versions / script_drafts.
     */
    public const MAX_TITLE_LENGTH = 160;

    /**
     * @param  list<SceneResult>  $scenes
     */
    private function __construct(
        public readonly string $title,
        public readonly ?string $hook,
        public readonly ?string $closingCta,
        public readonly array $scenes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array{max_scenes?: int, include_closing?: bool}  $options
     *
     * @throws InvalidProviderOutputException
     */
    public static function fromArray(array $data, array $options = []): self
    {
        $title = self::stringOrNull(Arr::get($data, 'title'));

        if ($title === null) {
            throw new InvalidProviderOutputException('Script title is missing.');
        }

        $title = mb_substr($title, 0, self::MAX_TITLE_LENGTH);

        $hook = self::stringOrNull(Arr::get($data, 'hook'));

        $rawScenes = Arr::get($data, 'scenes');

        if (! is_array($rawScenes) || $rawScenes === []) {
            throw new InvalidProviderOutputException('Script scenes are missing.');
        }

        $rawScenes = array_slice(array_values($rawScenes), 0, self::resolveMaxScenes($options));

        $scenes = [];
        foreach ($rawScenes as $index => $scene) {
            if (! is_array($scene)) {
                throw new InvalidProviderOutputException('Script scene entry is invalid.');
            }

            $narration = self::stringOrNull(Arr::get($scene, 'narration'));

            if ($narration === null) {
                throw new InvalidProviderOutputException('Script scene narration is missing.');
            }

            $duration = Arr::get($scene, 'estimated_duration_seconds');

            if ($duration !== null && (! is_numeric($duration) || (int) $duration <= 0)) {
                throw new InvalidProviderOutputException('Script scene duration is invalid.');
            }

            $scenes[] = new SceneResult(
                sceneNumber: $index + 1,
                narration: $narration,
                visualDirection: self::stringOrNull(Arr::get($scene, 'visual_direction')),
                estimatedDurationSeconds: $duration === null ? null : (int) $duration,
            );
        }

        $closingCta = self::stringOrNull(Arr::get($data, 'closing_cta'));

        if (! self::resolveIncludeClosing($options)) {
            $closingCta = null;
        } elseif ($closingCta === null) {
            throw new InvalidProviderOutputException('Script closing CTA is missing.');
        }

        return new self($title, $hook, $closingCta, $scenes);
    }

    /**
     * @return array{title: string, hook: ?string, closing_cta: ?string, scenes: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'hook' => $this->hook,
            'closing_cta' => $this->closingCta,
            'scenes' => array_map(
                static fn (SceneResult $scene): array => $scene->toArray(),
                $this->scenes,
            ),
        ];
    }

    public function sceneCount(): int
    {
        return count($this->scenes);
    }

    /**
     * @param  array{max_scenes?: int}  $options
     */
    private static function resolveMaxScenes(array $options): int
    {
        $max = (int) ($options['max_scenes'] ?? self::MAX_SCENES);

        return max(self::MIN_SCENES, min(self::MAX_SCENES, $max));
    }

    /**
     * @param  array{include_closing?: bool}  $options
     */
    private static function resolveIncludeClosing(array $options): bool
    {
        return (bool) ($options['include_closing'] ?? true);
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
