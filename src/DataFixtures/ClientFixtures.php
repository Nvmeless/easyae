<?php

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;
use App\Entity\Client;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ClientFixtures extends Fixture implements DependentFixtureInterface
{
    public const PREFIX = "client#";
    public const POOL_MIN = 0;
    public const POOL_MAX = 40;

    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        $adminUser = $this->getReference(UserFixtures::ADMIN_REF);
        $facturationModelRefs = [];
        
        for ($i = FacturationModelFixtures::POOL_MIN; $i < FacturationModelFixtures::POOL_MAX; $i++) {
            $facturationModelRefs[] = FacturationModelFixtures::PREFIX . $i;
        }

        $contactRefs = [];
        
        for ($i = ContactFixtures::POOL_MIN; $i < ContactFixtures::POOL_MAX; $i++) {
            $contactRefs[] = ContactFixtures::PREFIX . $i;
        }

        $facturationModelCount = count($facturationModelRefs);
        $contactCount = count($contactRefs);

        for ($i = self::POOL_MIN; $i < self::POOL_MAX; $i++) {
            $client = new Client();
            $client->setName($this->faker->company);


            $createdAt = $this->faker->dateTimeBetween('-2 years', 'now');
            $client->setCreatedAt($createdAt);
            $client->setUpdatedAt($this->faker->dateTimeBetween($createdAt, 'now'));
            $client->setStatus($this->faker->randomElement(['on', 'off']));
            $client->setCreatedBy($adminUser->getId());
            $client->setUpdatedBy($adminUser->getId());
            $client->setStatus('on');

            if ($facturationModelCount > 0) {
                $facturationModelIndex = min($i, $facturationModelCount - 1);
                $facturationModel = $this->getReference($facturationModelRefs[$facturationModelIndex]);
                $client->setFacturationModel($facturationModel);
            }

            if ($contactCount > 0) {
                $contactIndex = min($i, $contactCount - 1);
                $contact = $this->getReference($contactRefs[$contactIndex]);
                $client->setContact($contact);
            }

            $manager->persist($client);
            $this->addReference(self::PREFIX . $i, $client);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            FacturationModelFixtures::class,
            ContactFixtures::class,
        ];
    }
}
