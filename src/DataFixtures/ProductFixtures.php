<?php

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;
use App\Entity\Product;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

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
        $adminUser = $this->getReference(UserFixtures::ADMIN_REF);
        $now = new \DateTime();
        $prefixType = ProductTypeFixtures::PREFIX;
        $typeRefs = [];
        for ($i = ProductTypeFixtures::POOL_MIN; $i < ProductTypeFixtures::POOL_MAX; $i++) {
            $typeRefs[] = $prefixType . $i;
        }
        $prefixQuantityType = QuantityTypeFixtures::PREFIX;
        $quantityTypeRefs = [];
        for ($i = QuantityTypeFixtures::POOL_MIN; $i < QuantityTypeFixtures::POOL_MAX; $i++) {
            $quantityTypeRefs[] = $prefixQuantityType . $i;
        }

        for ($i = self::POOL_MIN; $i < self::POOL_MAX; ++$i) {
            $dateCreated = $this->faker->dateTimeInInterval('-1 year', '+1 year');
            $dateUpdated = $this->faker->dateTimeBetween($dateCreated, $now);
            $type = $this->getReference($typeRefs[array_rand($typeRefs, 1)]);
            $statuses = ['on', 'off'];
            $quantityType = $this->getReference($quantityTypeRefs[array_rand($quantityTypeRefs, 1)]);
            $product = new Product();
            $product
                ->setType($type)
                ->setQuantityType($quantityType)
                ->setQuantity($this->faker->randomDigit())
                ->setCreatedAt($dateCreated)
                ->setUpdatedAt($dateUpdated)
                ->setCreatedBy($adminUser->getId())
                ->setUpdatedBy($adminUser->getId())
                ->setStatus($statuses[rand(0, count($statuses) - 1)])
                ->setPrice($this->faker->randomFloat(2, 10, 100))
                ->setPriceUnit($this->faker->randomFloat(2, 10, 100))
                ->setFees($this->faker->numberBetween(0, 100));
            $manager->persist($product);
            $this->addReference(self::PREFIX . $i, $product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ProductTypeFixtures::class,
            QuantityTypeFixtures::class
        ];
    }

}
