<?php

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Facturation;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class FacturationFixtures extends Fixture implements DependentFixtureInterface
{
    public const PREFIX = "facturation#";
    public const PPOL_MIN = 0;
    public const PPOL_MAX = 20;

    private Generator $faker;
    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {

        $now = new \DateTime();
        $prefixContrat = ContratFixtures::PREFIX;
        $contratRefs = [];
        for ($i = ContratFixtures::POOL_MIN; $i < ContratFixtures::POOL_MAX; $i++) {
            $contratRefs[] = $prefixContrat . $i;
        }

        for ($i = self::PPOL_MIN; $i < self::PPOL_MAX; $i++) {
            $dateCreated = $this->faker->dateTimeInInterval('-1 year', '+1 year');
            $dateUpdated = $this->faker->dateTimeBetween($dateCreated, $now);
            $contrat = $this->getReference($contratRefs[$i]);             
            $facturation = new Facturation();
            $facturation
                ->setName($this->faker->numerify('facturation-###'))
                ->setStatus("on")
                ->setContrat($contrat)
                ->setCreatedAt($dateCreated)
                ->setUpdatedAt($dateUpdated)
            ;

            $manager->persist($facturation);
            $this->addReference(self::PREFIX . $i, $facturation);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            ContratFixtures::class
        ];
    }
}
