<?php

namespace Solinte\SdkPhp\Exceptions;

use Exception;

class ValidationException extends Exception {
    private array $errors;

    public function __construct(string $message = '', array $errors = [], Exception $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function hasErrors(): bool {
        return !empty($this->errors);
    }
}
