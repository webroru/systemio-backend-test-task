<?php

declare(strict_types=1);

namespace App\Factory;

use App\Payment\PaymentProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use InvalidArgumentException;

class PaymentProcessorFactory
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create(string $processorType): PaymentProcessorInterface
    {
        $processors = $this->container->findTaggedServiceIds('payment_processor');

        if (!isset($processors[$processorType])) {
            throw new InvalidArgumentException('Unsupported payment processor type: ' . $processorType);
        }

        return $this->container->get($processors[$processorType]);
    }
}
