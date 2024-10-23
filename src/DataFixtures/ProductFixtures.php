<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{

    public const PREFIX = "product#";
    public const POOL_MIN = 0;
    public const POOL_MAX = 20;

    private Generator $faker;
    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTime();
        $prefix = ProductTypeFixtures::PREFIX;
        $typeRefs = [];
        for ($i = ProductTypeFixtures::POOL_MIN; $i < ProductTypeFixtures::POOL_MAX; $i++) {
            $typeRefs[] = $prefix . $i;
        }

        for ($i = self::POOL_MIN; $i < self::POOL_MAX; ++$i) {
            $dateCreated = $this->faker->dateTimeInInterval('-1 year', '+1 year');
            $dateUpdated = $this->faker->dateTimeBetween($dateCreated, $now);
            $type = $this->getReference($typeRefs[array_rand($typeRefs, 1)]);
            $product = new Product();
            $statuses = ['on', 'off'];
            $product
                ->setType($type)
                ->setQuantity($this->faker->randomDigit())
                ->setCreatedAt($dateCreated)
                ->setUpdatedAt($dateUpdated)
                ->setStatus($statuses[rand(0, count($statuses)-1)])
                ->setPrice($this->faker->randomFloat(2, 10, 100))
                ->setPriceUnit($this->faker->randomFloat(2, 10, 100));
            $manager->persist($product);
            $this->addReference(self::PREFIX . $i, $product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProductTypeFixtures::class,
        ];
    }
}
