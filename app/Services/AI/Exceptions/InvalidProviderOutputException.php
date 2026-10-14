<?php

namespace App\Services\AI\Exceptions;

/**
 * Raised when a provider returns a response that cannot be turned into a
 * valid script (missing fields, malformed JSON, truncated or blocked output).
 */
class InvalidProviderOutputException extends ProviderException {}
