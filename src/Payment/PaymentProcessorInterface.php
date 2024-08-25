<?php

declare(strict_types=1);

namespace App\Payment;

use App\Exception\PaymentException;

interface PaymentProcessorInterface
{
    /**
     * @throws PaymentException
     */
    public function pay(float $amount): void;
}
