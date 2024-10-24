<?php

namespace App\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker\Factory;
use Faker\Generator;
use App\Entity\Account;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AccountFixtures extends Fixture implements DependentFixtureInterface
{
    public const PREFIX = "account#";
    public const POOL_MIN = 0;
    public const POOL_MAX = 10;

    private Generator $faker;
    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTime();
        $prefixClient = ClientFixtures::PREFIX;
        $clientRefs = [];
        for ($i = ClientFixtures::POOL_MIN; $i < ClientFixtures::POOL_MAX; $i++) {
            $clientRefs[] = $prefixClient . $i;
        }

        for ($i = self::POOL_MIN; $i < self::POOL_MAX; $i++) {
            $dateCreated = $this->faker->dateTimeInInterval('-1 year', '+1 year');
            $dateUpdated = $this->faker->dateTimeBetween($dateCreated, $now);
            $client = $this->getReference($clientRefs[array_rand($clientRefs, 1)]);
            $account = new Account();
            $account
                ->setName($this->faker->numerify('account-###'))
                ->setClient($client)
                ->setCreatedAt($dateCreated)
                ->setUpdatedAt($dateUpdated)
                ->setStatus('on')
            ;
            $manager->persist($account);
            $this->addReference(self::PREFIX . $i, $account);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            ClientFixtures::class
        ];
    }
}
