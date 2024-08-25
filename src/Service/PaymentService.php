<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\PaymentException;
use App\Payment\PaymentProcessorInterface;
use Psr\Log\LoggerInterface;

class PaymentService
{
    public function __construct(
        private readonly PaymentProcessorInterface $paymentProcessor,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function process(float $amount): void
    {
        try {
            $this->paymentProcessor->pay($amount);
        } catch (PaymentException $e) {
            $this->logger->error(sprintf('Payment Error: `%s`', $e->getMessage()));
        }
    }
}
