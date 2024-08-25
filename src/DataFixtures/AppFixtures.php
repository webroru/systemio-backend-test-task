<?php

namespace App\DataFixtures;

use App\Entity\Coupon;
use App\Entity\Product;
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
                'discount' => 10
            ],
            [
                'code' => 'P100 ',
                'discount' => 100
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
                ->setDiscountAmount($coupon['discount'])
            ;
            $manager->persist($coupon);
        }

        $manager->flush();
    }
}
