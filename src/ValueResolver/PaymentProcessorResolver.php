<?php

declare(strict_types=1);

namespace App\ValueResolver;

use App\Enum\PaymentType;
use App\Payment\PaymentProcessorInterface;
use App\Payment\Processor\PayPalPayment;
use App\Payment\Processor\StripePayment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

readonly class PaymentProcessorResolver implements ValueResolverInterface
{
    public function __construct(
        private PayPalPayment $payPalPayment,
        private StripePayment $stripePayment,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() === PaymentProcessorInterface::class && $argument->getName() === 'paymentProcessor') {
            yield match (PaymentType::from($request->getPayload()->get('paymentProcessor'))) {
                PaymentType::PAYPAL => $this->payPalPayment,
                PaymentType::STRIPE => $this->stripePayment,
            };
        }
    }
}
