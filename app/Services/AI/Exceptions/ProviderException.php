<?php

namespace App\Services\AI\Exceptions;

use RuntimeException;

/**
 * Raised when an AI provider request fails at runtime (transport error,
 * non-success HTTP status, or unusable provider output).
 */
class ProviderException extends RuntimeException {}
