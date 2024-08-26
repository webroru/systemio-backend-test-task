<?php

declare(strict_types=1);

namespace App\Dto;

use App\Validator\ValidTaxNumber;
use Symfony\Component\Validator\Constraints as Assert;

readonly class PurchaseRequest
{
    public function __construct(
        #[Assert\NotBlank(message: "Product ID is required.")]
        #[Assert\Type(type: "integer", message: "Product ID must be an integer.")]
        public ?int $product,
        #[Assert\NotBlank(message: "Tax number is required.")]
        #[ValidTaxNumber] // Ваша пользовательская валидация для налогового номера
        public ?string $taxNumber,
        #[Assert\NotBlank(message: "Coupon code is required.")]
        #[Assert\Type(type: "string", message: "Coupon code must be a string.")]
        public ?string $couponCode,
        #[Assert\NotBlank(message: "Payment processor is required.")]
        #[Assert\Choice(choices: ["paypal", "stripe"], message: "Invalid payment processor.")]
        public ?string $paymentProcessor
    ) {
    }
}
