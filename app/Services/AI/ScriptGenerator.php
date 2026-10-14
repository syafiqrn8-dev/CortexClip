<?php

namespace App\Services\AI;

use App\Models\ContentBrief;
use App\Models\Project;
use App\Services\AI\Contracts\ScriptProvider;
use App\Services\AI\Exceptions\ProviderConfigurationException;
use App\Services\AI\Exceptions\ProviderException;
use Illuminate\Contracts\Container\Container;

/**
 * Provider-agnostic orchestrator for script generation.
 *
 * Resolves the configured {@see ScriptProvider}, delegates generation to it,
 * and validates/normalizes the raw output through {@see ScriptResult}.
 */
class ScriptGenerator
{
    private const DEFAULT_PROVIDER = 'mock';

    /** @var array<string, ScriptProvider> */
    private array $resolved = [];

    public function __construct(private readonly Container $container) {}

    /**
     * @param  array{max_scenes?: int, include_closing?: bool}  $options
     *
     * @throws ProviderConfigurationException
     * @throws ProviderException
     */
    public function generate(Project $project, ContentBrief $brief, array $options = []): ScriptResult
    {
        $raw = $this->provider()->generate($project, $brief, $options);

        return ScriptResult::fromArray($raw, $options);
    }

    /**
     * Resolve (and memoize) the configured provider.
     *
     * @throws ProviderConfigurationException
     */
    public function provider(): ScriptProvider
    {
        $name = (string) config('services.ai.provider', self::DEFAULT_PROVIDER);

        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        $providers = config('services.ai.providers', []);
        $class = is_array($providers) ? ($providers[$name] ?? null) : null;

        if (! is_string($class) || ! class_exists($class)) {
            throw new ProviderConfigurationException("AI provider [{$name}] is not configured.");
        }

        $instance = $this->container->make($class);

        if (! $instance instanceof ScriptProvider) {
            throw new ProviderConfigurationException("AI provider [{$name}] is invalid.");
        }

        return $this->resolved[$name] = $instance;
    }
}
