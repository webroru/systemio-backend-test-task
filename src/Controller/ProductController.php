<?php

declare(strict_types=1);

namespace App\Controller;

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
        Request $request,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $product = $this->em->getRepository(Product::class)->find($data['product']);
        $coupon = $this->em->getRepository(Coupon::class)->findOneBy(['code' => $data['couponCode']]);

        try {
            $price = $this->calculator->calculatePrice($product, $coupon, $data['taxNumber']);
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

        $product = $this->em->getRepository(Product::class)->find($data['product']);
        $coupon = $this->em->getRepository(Coupon::class)->findOneBy(['code' => $data['couponCode']]);

        try {
            $price = $this->calculator->calculatePrice($product, $coupon, $data['taxNumber']);
            $processorType = (string) $data['paymentProcessor'];
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

    private function getPaymentService(string $processorType): PaymentProcessorInterface
    {
        return match ($processorType) {
            'paypal' => $this->payPalPayment,
            'stripe' => $this->stripePayment,
            default => throw new \InvalidArgumentException('Invalid payment processor type'),
        };
    }
}
