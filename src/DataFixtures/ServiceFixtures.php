<?php

namespace App\DataFixtures;

use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ServiceFixtures extends Fixture implements DependentFixtureInterface
{
    public const PREFIX = "service#";
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

        for ($i = self::POOL_MIN; $i < self::POOL_MAX; $i++) {
            $dateCreated = $this->faker->dateTimeInInterval('-1 year', '+1 year');
            $dateUpdated = $this->faker->dateTimeBetween($dateCreated, $now);
            $service = new Service();

            $service
                ->setName($this->faker->numerify('service-###'))
                ->setUpdatedAt($dateCreated)
                ->setCreatedAt($dateUpdated)
                ->setStatus("on")
                ->setCreatedBy($adminUser->getId())
                ->setUpdatedBy($adminUser->getId())
            ;

            $manager->persist($service);
            $this->addReference(self::PREFIX . $i, $service);
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
