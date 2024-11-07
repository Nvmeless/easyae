<?php

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;
use App\Entity\QuantityType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class QuantityTypeFixtures extends Fixture implements DependentFixtureInterface
{
    public const PREFIX = "quantityType#";
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
        $quantityTypes = [
            'kebab',
            'euro',
            'mètre',
            'litre',
            'kilogramme',
            'pièce',
            'paquet',
            'boîte',
            'bouteille',
            'sachet',
            'gramme',
            'centimètre',
            'millilitre',
            'tonne',
            'hectare',
            'kilomètre',
            'minute',
            'heure',
            'jour',
            'semaine',
            'mois',
            'année',
            'décennie',
            'siècle',
            'millénaire',
            'joule',
            'calorie',
            'watt',
            'ampère',
            'volt',
            'ohm',
            'hertz',
            'pascal',
            'bar',
            'newton',
        ];

        for ($i = self::POOL_MIN; $i < self::POOL_MAX; $i++) {
            $dateCreated = $this->faker->dateTimeInInterval('-1 year', '+1 year');
            $dateUpdated = $this->faker->dateTimeBetween($dateCreated, $now);
            $quantityType = new QuantityType();
            $quantityType
                ->setName($this->faker->randomElement($quantityTypes))
                ->setCreatedAt($dateCreated)
                ->setUpdatedAt($dateUpdated)
                ->setStatus('on')
                ->setCreatedBy($adminUser->getId())
                ->setUpdatedBy($adminUser->getId());
            $manager->persist($quantityType);
            $this->addReference(self::PREFIX . $i, $quantityType);
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
