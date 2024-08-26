<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidTaxNumber extends Constraint
{
    public function __construct(
        public ?string $message = 'The tax number "{{ value }}" is not valid for the country.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(['message' => $message], $groups, $payload);
    }
}
