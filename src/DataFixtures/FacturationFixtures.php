<?php

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Facturation;
use App\Entity\FacturationModel;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class FacturationFixtures extends Fixture implements DependentFixtureInterface
{
    public const PREFIX = "facturation#";
    public const PPOL_MIN = 0;
    public const PPOL_MAX = 3;

    private Generator $faker;
    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        $adminUser = $this->getReference(UserFixtures::ADMIN_REF);
        $now = new \DateTime();
        $prefixContrat = ContratFixtures::PREFIX;
        $prefixModel = FacturationModelFixtures::PREFIX;
        $contratRefs = [];
        for ($i = ContratFixtures::POOL_MIN; $i < ContratFixtures::POOL_MAX; $i++) {
            $contratRefs[] = $prefixContrat . $i;
        }

        $modelRefs = [];
        for ($i = FacturationModelFixtures::POOL_MIN; $i < FacturationModelFixtures::POOL_MAX; $i++) {
            $modelRefs[] = $prefixModel . $i;
        }

        for ($i = self::PPOL_MIN; $i < self::PPOL_MAX; $i++) {
            $dateCreated = $this->faker->dateTimeInInterval('-1 year', '+1 year');
            $dateUpdated = $this->faker->dateTimeBetween($dateCreated, $now);
            $contrat = $this->getReference($contratRefs[$i]);
            $model = $this->getReference($modelRefs[$i]);    
            $facturation = new Facturation();
            $facturation
                ->setName($this->faker->numerify('facturation-###'))
                ->setStatus("on")
                ->setContrat($contrat)
                ->setModel($model)
                ->setCreatedAt($dateCreated)
                ->setUpdatedAt($dateUpdated)
                ->setCreatedBy($adminUser->getId())
                ->setUpdatedBy($adminUser->getId())
            ;

            $manager->persist($facturation);
            $this->addReference(self::PREFIX . $i, $facturation);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
            ContratFixtures::class
        ];
    }
}
