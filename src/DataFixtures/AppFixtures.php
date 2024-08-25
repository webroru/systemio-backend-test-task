<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Coupon;
use App\Entity\Product;
use App\Enum\CouponType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $products = [
            [
                'name' => 'Iphone ',
                'price' => 100
            ],
            [
                'name' => 'Наушники  ',
                'price' => 20
            ],
            [
                'name' => 'Чехол ',
                'price' => 10
            ],
        ];

        $coupons = [
            [
                'code' => 'P10',
                'value' => 10,
                'type' => CouponType::PERCENTAGE,
            ],
            [
                'code' => 'P100 ',
                'value' => 100,
                'type' => CouponType::PERCENTAGE,
            ],
            [
                'code' => 'D15 ',
                'value' => 15,
                'type' => CouponType::FIXED,
            ],
            [
                'code' => 'D10 ',
                'value' => 10,
                'type' => CouponType::FIXED,
            ],
        ];

        foreach ($products as $product) {
            $product = (new Product())
                ->setName($product['name'])
                ->setPrice($product['price'])
            ;
            $manager->persist($product);
        }

        foreach ($coupons as $coupon) {
            $coupon = (new Coupon())
                ->setCode($coupon['code'])
                ->setValue($coupon['value'])
                ->setType($coupon['type'])
            ;
            $manager->persist($coupon);
        }

        $manager->flush();
    }
}
