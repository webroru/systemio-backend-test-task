<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CalculatePriceRequest;
use App\Dto\PurchaseRequest;
use App\Entity\Product;
use App\Entity\Coupon;
use App\Exception\UserException;
use App\Payment\PaymentProcessorInterface;
use App\Payment\Processor\PayPalPayment;
use App\Payment\Processor\StripePayment;
use App\Service\PriceCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

class ProductController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PriceCalculator $calculator,
        private readonly LoggerInterface $logger,
        private readonly PayPalPayment $payPalPayment,
        private readonly StripePayment $stripePayment,
    ) {
    }

    #[Route('/calculate-price', name: 'calculate_price', methods: ['POST'])]
    public function calculatePrice(
        #[MapRequestPayload] CalculatePriceRequest $dto
    ): JsonResponse {
        $product = $this->em->getRepository(Product::class)->find((string) $dto->product);
        $coupon = $this->em->getRepository(Coupon::class)->findOneBy(['code' => $dto->couponCode]);

        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $price = $this->calculator->calculatePrice($product, $coupon, $dto->taxNumber);
            return $this->json(['price' => $price]);
        } catch (UserException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception) {
            return $this->json(['error' => 'Unexpected error occurred'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/purchase', name: 'purchase', methods: ['POST'])]
    public function purchase(
        #[MapRequestPayload] PurchaseRequest $dto,
        PaymentProcessorInterface $paymentProcessor,
    ): JsonResponse {
        $product = $this->em->getRepository(Product::class)->find($dto->product);
        $coupon = $this->em->getRepository(Coupon::class)->findOneBy(['code' => $dto->couponCode]);

        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $price = $this->calculator->calculatePrice($product, $coupon, $dto->taxNumber);
            $paymentProcessor->pay($price);
        } catch (UserException $e) {
            $this->logger->error("Purchase error: {$e->getMessage()}");
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error("Purchase error: {$e->getMessage()}");
            return new JsonResponse(['error' => 'Unexpected error occurred'], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['status' => 'success'], Response::HTTP_OK);
    }

    private function getPaymentService(string $processorType): PaymentProcessorInterface
    {
        return match ($processorType) {
            'paypal' => $this->payPalPayment,
            'stripe' => $this->stripePayment,
            default => throw new \InvalidArgumentException('Invalid payment processor type'),
        };
    }
}