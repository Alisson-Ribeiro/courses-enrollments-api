<?php

namespace App\Exceptions;

class BusinessRuleException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $rule = ''
    ) {
        parent::__construct($message);
    }

    public function getRule(): string
    {
        return $this->rule;
    }
}
