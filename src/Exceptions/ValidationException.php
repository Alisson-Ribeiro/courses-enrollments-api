<?php

namespace App\Exceptions;

class ValidationException extends \RuntimeException
{
    public function __construct(
        string $message = 'Dados inválidos.',
        private readonly array $details = []
    ) {
        parent::__construct($message);
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
