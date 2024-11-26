<?php

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Fonction;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class FonctionFixtures extends Fixture implements DependentFixtureInterface
{
    public const PREFIX = "fonction#";
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

        for ($i = self::POOL_MIN; $i < self::POOL_MAX; $i++) {
            $fonction = new Fonction();

            $fonction->setName($this->faker->jobTitle());
            $fonction->setType($this->faker->word());
            $fonction->setCreatedAt($this->faker->dateTimeBetween('-2 years', 'now'));
            $fonction->setUpdatedAt($this->faker->dateTimeBetween('-1 years', 'now'));
            $fonction->setStatus($this->faker->randomElement(['on', 'off']));
            $fonction->setCreatedBy($adminUser->getId());
            $fonction->setUpdatedBy($adminUser->getId());

            $manager->persist($fonction);
            $this->addReference(self::PREFIX . $i, $fonction);
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
