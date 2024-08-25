<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\UserException;
use App\Payment\PaymentProcessorInterface;
use Psr\Log\LoggerInterface;

readonly class PaymentService
{
    public function __construct(
        private PaymentProcessorInterface $paymentProcessor,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws UserException
     */
    public function process(float $amount): void
    {
        try {
            $this->paymentProcessor->pay($amount);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Payment Error: `%s`', $e->getMessage()));
            throw new UserException('Payment Error');
        }
    }
}
