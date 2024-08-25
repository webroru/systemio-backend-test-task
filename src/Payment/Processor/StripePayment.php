<?php

declare(strict_types=1);

namespace App\Payment\Processor;

use App\Exception\PaymentException;
use App\Payment\PaymentProcessorInterface;
use Psr\Log\LoggerInterface;
use Systemeio\TestForCandidates\PaymentProcessor\StripePaymentProcessor;

class StripePayment implements PaymentProcessorInterface
{
    public function __construct(
        private readonly StripePaymentProcessor $processor,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws PaymentException
     */
    public function pay(float $amount): void
    {
        if (!$this->processor->processPayment($amount)) {
            throw new PaymentException('Error processing payment with PayPal');
        }

        $this->logger->info('Stripe Payment processed.');
    }
}
