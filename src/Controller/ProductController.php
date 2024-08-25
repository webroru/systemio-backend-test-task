<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Coupon;
use App\Exception\UserException;
use App\Factory\PaymentServiceFactory;
use App\Service\PriceCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    public function __construct(
        private readonly PaymentServiceFactory $paymentServiceFactory,
        private readonly EntityManagerInterface $em,
        private readonly PriceCalculator $calculator,
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
            $processorType = $data['paymentProcessor'];
            $paymentService = $this->paymentServiceFactory->create($processorType);
            $paymentService->process($price);
        } catch (UserException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception) {
            return new JsonResponse(['error' => 'Unexpected error occurred'], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['status' => 'success'], Response::HTTP_OK);
    }
}
