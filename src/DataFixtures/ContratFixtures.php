<?php

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;
use App\Entity\Contrat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ContratFixtures extends Fixture implements DependentFixtureInterface
{
    public const PREFIX = "contrat#";
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
        $prefixType = ContratTypeFixtures::PREFIX;
        $typeRefs = [];
        for ($i = ContratTypeFixtures::POOL_MIN; $i < ContratTypeFixtures::POOL_MAX; $i++) {
            $typeRefs[] = $prefixType . $i;
        }

        $prefixClient = ClientFixtures::PREFIX;
        $clientRefs = [];
        for ($i = ClientFixtures::POOL_MIN; $i < ClientFixtures::POOL_MAX; $i++) {
            $clientRefs[] = $prefixClient . $i;
        }

        $prefixProduct = ProductFixtures::PREFIX;
        $productRefs = [];
        for ($i = ProductFixtures::POOL_MIN; $i < ProductFixtures::POOL_MAX; $i++) {
            $productRefs[] = $prefixProduct . $i;
        }

        $adminUser = $this->getReference(UserFixtures::ADMIN_REF);

        for ($count = self::POOL_MIN; $count < self::POOL_MAX; $count++) {
            $contrat = new Contrat();

            $dateCreated = $this->faker->dateTimeInInterval('-1 year', '+1 year');
            $dateUpdated = $this->faker->dateTimeBetween($dateCreated, $now);

            $dateStarted = $this->faker->dateTimeInInterval('-2 year', '+2 year');
            $dateEnded = $this->faker->dateTimeBetween($dateStarted, '+4 year');

            $type = $this->getReference($typeRefs[array_rand($typeRefs, 1)]);
            $client = $this->getReference($clientRefs[array_rand($clientRefs, 1)]);
            $product = $this->getReference($productRefs[array_rand($productRefs, 1)]);
            for ($c = 0; $c < $this->faker->numberBetween(1, 9); $c++) {
                $contrat->addProduct($product);
            }

            $contrat->setName($this->faker->numerify("Contrat-###"))
                ->setCreatedAt($dateCreated)
                ->setUpdatedAt($dateUpdated)
                ->setCreatedBy($adminUser->getId())
                ->setUpdatedBy($adminUser->getId())
                ->setStatus("on")
                ->setType($type)
                ->setClient($client)
                ->setStartAt($dateStarted)
                ->setEndAt($dateEnded)
                ->setDone($this->faker->numberBetween(0, 1));

            $manager->persist($contrat);
            $this->addReference(self::PREFIX . $count, $contrat);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
            ContratTypeFixtures::class,
            ClientFixtures::class,
            ProductFixtures::class
        ];
    }
}
