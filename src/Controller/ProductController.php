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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PriceCalculator $calculator,
        private readonly LoggerInterface $logger,
        private readonly PayPalPayment $payPalPayment,
        private readonly StripePayment $stripePayment,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/calculate-price', name: 'calculate_price', methods: ['POST'])]
    public function calculatePrice(
        Request $request,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $calculatePriceRequest = new CalculatePriceRequest(
            product: $data['product'] ?? null,
            taxNumber: $data['taxNumber'] ?? null,
            couponCode: $data['couponCode'] ?? null,
        );

        $violations = $this->validator->validate($calculatePriceRequest);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->em->getRepository(Product::class)->find((string) $calculatePriceRequest->product);
        $coupon = $this->em->getRepository(Coupon::class)->findOneBy(['code' => $calculatePriceRequest->couponCode]);

        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $price = $this->calculator->calculatePrice($product, $coupon, $calculatePriceRequest->taxNumber);
            return $this->json(['price' => $price]);
        } catch (UserException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception) {
            return $this->json(['error' => 'Unexpected error occurred'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/purchase', name: 'purchase', methods: ['POST'])]
    public function purchase(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $purchaseRequest = new PurchaseRequest(
            product: $data['product'] ?? null,
            taxNumber: $data['taxNumber'] ?? null,
            couponCode: $data['couponCode'] ?? null,
            paymentProcessor: $data['paymentProcessor'] ?? null,
        );

        $violations = $this->validator->validate($purchaseRequest);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->em->getRepository(Product::class)->find($purchaseRequest->product);
        $coupon = $this->em->getRepository(Coupon::class)->findOneBy(['code' => $purchaseRequest->couponCode]);

        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $price = $this->calculator->calculatePrice($product, $coupon, $purchaseRequest->taxNumber);
            $processorType = $purchaseRequest->paymentProcessor;
            $paymentService = $this->getPaymentService($processorType);
            $paymentService->pay($price);
        } catch (UserException $e) {
            $this->logger->error("Purchase error: {$e->getMessage()}");
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error("Purchase error: {$e->getMessage()}");
            return new JsonResponse(['error' => 'Unexpected error occurred'], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['status' => 'success'], Response::HTTP_OK);
    }

    // TODO: move to ArgumentValueResolver
    private function getPaymentService(string $processorType): PaymentProcessorInterface
    {
        return match ($processorType) {
            'paypal' => $this->payPalPayment,
            'stripe' => $this->stripePayment,
            default => throw new \InvalidArgumentException('Invalid payment processor type'),
        };
    }
}
