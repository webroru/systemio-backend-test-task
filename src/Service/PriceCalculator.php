<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Product;
use App\Entity\Coupon;
use App\Enum\CouponType;
use App\Exception\UserException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class PriceCalculator
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    private const float DE_TAX_RATE = 0.19;
    private const float IT_TAX_RATE = 0.22;
    private const float FR_TAX_RATE = 0.20;
    private const float GR_TAX_RATE = 0.24;

    /**
     * @throws UserException
     */
    public function calculatePrice(Product $product, ?Coupon $coupon, string $taxNumber): float
    {
        try {
            $price = $product->getPrice();

            if ($coupon) {
                $price = $this->applyCoupon($product->getPrice(), $coupon);
            }

            $taxRate = $this->getTaxRateByTaxNumber($taxNumber);
            $price += $price * ($taxRate / 100);

            return $price;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Calculate price error: `%s`', $e->getMessage()));
            throw new UserException('Calculate price error');
        }
    }

    private function applyCoupon(float $amount, Coupon $coupon): float
    {
        switch ($coupon->getType()) {
            case CouponType::FIXED:
                $amount -= $coupon->getValue();
                break;
            case CouponType::PERCENTAGE:
                $amount -= ($amount * $coupon->getValue() / 100);
                break;
        }

        return max(0, $amount);
    }

    private function getTaxRateByTaxNumber(string $taxNumber): float
    {
        return match (1) {
            preg_match('/^DE\d{9}$/', $taxNumber) => self::DE_TAX_RATE,
            preg_match('/^IT\d{11}$/', $taxNumber) => self::IT_TAX_RATE,
            preg_match('/^FR\d{2}\d{9}$/', $taxNumber) => self::FR_TAX_RATE,
            preg_match('/^GR\d{9}$/', $taxNumber) => self::GR_TAX_RATE,
            default => throw new InvalidArgumentException('Unsupported tax number format'),
        };
    }
}
