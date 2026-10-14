<?php

namespace App\Services\AI\Exceptions;

/**
 * Raised when the configured AI provider is unknown, invalid, or missing
 * required credentials. This is a server-side setup error, not a transient
 * provider failure.
 */
class ProviderConfigurationException extends ProviderException {}
