<?php

declare(strict_types=1);

namespace App\Factory;

use App\Service\PaymentService;
use Psr\Log\LoggerInterface;

readonly class PaymentServiceFactory
{
    public function __construct(
        private PaymentProcessorFactory $paymentProcessorFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function create(string $processorType): PaymentService
    {
        $paymentProcessor = $this->paymentProcessorFactory->create($processorType);
        return new PaymentService($paymentProcessor, $this->logger);
    }
}
