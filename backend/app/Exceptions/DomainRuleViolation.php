<?php

namespace App\Exceptions;

use DomainException;

class DomainRuleViolation extends DomainException
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly string $ruleCode,
        string $message,
        public readonly int $status = 409,
        public readonly array $headers = [],
    ) {
        parent::__construct($message);
    }
}
