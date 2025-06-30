<?php

namespace Solinte\SdkPhp\Exceptions;

use Exception;

class OAuthException extends Exception {
    private ?int $statusCode;

    public function __construct(string $message = '', int $statusCode = null, Exception $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): ?int {
        return $this->statusCode;
    }
}
