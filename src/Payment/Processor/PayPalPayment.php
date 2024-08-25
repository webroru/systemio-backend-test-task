<?php

declare(strict_types=1);

namespace App\Payment\Processor;

use App\Exception\PaymentException;
use App\Payment\PaymentProcessorInterface;
use Psr\Log\LoggerInterface;
use Systemeio\TestForCandidates\PaymentProcessor\PaypalPaymentProcessor;

class PayPalPayment implements PaymentProcessorInterface
{
    public function __construct(
        private readonly PaypalPaymentProcessor $processor,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws PaymentException
     */
    public function pay(float $amount): void
    {
        try {
            $this->processor->pay((int) $amount * 100);
            $this->logger->info('Payment processed with PayPal successfully');
        } catch (\Exception $e) {
            throw new PaymentException(sprintf('Error processing payment with PayPal: `%s`', $e->getMessage()));
        }
    }
}
