<?php

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;
use App\Entity\Client;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class ClientFixtures extends Fixture
{
    public const PREFIX = "client#";
    public const POOL_MIN = 0;
    public const POOL_MAX = 10;

    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = self::POOL_MIN; $i < self::POOL_MAX; $i++) {
            $client = new Client();
            $client->setName($this->faker->company);
            $createdAt = $this->faker->dateTimeBetween('-2 years', 'now');
            $client->setCreatedAt($createdAt);
            $client->setUpdatedAt($this->faker->dateTimeBetween($createdAt, 'now'));
            $client->setStatus($this->faker->randomElement(['on', 'off']));

            $manager->persist($client);

            $this->addReference(self::PREFIX . $i, $client);
        }

        $manager->flush();
    }
}

