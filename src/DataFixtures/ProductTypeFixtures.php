<?php

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;
use App\Entity\ProductType;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ProductTypeFixtures extends Fixture implements DependentFixtureInterface
{
    public const PREFIX = "productType#";
    public const POOL_MIN = 0;
    public const POOL_MAX = 10;
    private Generator $faker;
    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }
    public function load(ObjectManager $manager): void
    {
        $adminUser = $this->getReference(UserFixtures::ADMIN_REF);
        $now = new \DateTime();

        for ($i = self::POOL_MIN; $i < self::POOL_MAX; ++$i) {
            $dateCreated = $this->faker->dateTimeInInterval('-1 year', '+1 year');
            $dateUpdated = $this->faker->dateTimeBetween($dateCreated, $now);
            
            $productType = new ProductType();
            $productType
                ->setName($this->faker->numerify('product type-###'))
                ->setPrice($this->faker->randomFloat(2))
                ->setCreatedAt($dateCreated)
                ->setUpdatedAt($dateUpdated)
                ->setStatus('on')
                ->setCreatedBy($adminUser->getId())
                ->setUpdatedBy($adminUser->getId())
            ;
            $manager->persist($productType);
            $this->addReference(self::PREFIX . $i, $productType);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class
        ];
    }
}
