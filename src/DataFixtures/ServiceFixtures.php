<?php

namespace App\DataFixtures;

use App\Entity\Service;
use App\enum\EService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ServiceFixtures extends Fixture implements DependentFixtureInterface
{
    public const PREFIX = "service#";

    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        $adminUser = $this->getReference(UserFixtures::ADMIN_REF);
        $now = new \DateTime();

        foreach (EService::cases() as $item) {
            $dateCreated = $this->faker->dateTimeInInterval('-1 year', '+1 year');
            $dateUpdated = $this->faker->dateTimeBetween($dateCreated, $now);
            $service = new Service();

            $service
                ->setName($item->value)
                ->setCreatedAt($dateCreated)
                ->setUpdatedAt($dateUpdated)
                ->setStatus("on");
                ->setName($this->faker->numerify('service-###'))
                ->setUpdatedAt($dateCreated)
                ->setCreatedAt($dateUpdated)
                ->setStatus("on")
                ->setCreatedBy($adminUser->getId())
                ->setUpdatedBy($adminUser->getId())
            ;

            $manager->persist($service);
            $this->addReference(self::PREFIX . $item->value, $service);
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
