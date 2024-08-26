<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidTaxNumberValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidTaxNumber) {
            throw new UnexpectedTypeException($constraint, ValidTaxNumber::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $patterns = [
            '/^DE\d{9}$/',
            '/^IT\d{11}$/',
            '/^GR\d{9}$/',
            '/^FR[A-Z]{2}\d{9}$/',
        ];

        $valid = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
