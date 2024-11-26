<?php

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;
use App\Entity\InfoType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class InfoTypeFixtures extends Fixture implements DependentFixtureInterface
{
    
    public const PREFIX = "infoType";
    public const POOL_MIN = 0;
    public const POOL_MAX = 25;
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
        $infoType = new InfoType();
        $infoType->setName($this->faker->word());
        $infoType->setInfo("Personal Info ");
        $infoType->setCreatedAt($dateCreated);
        $infoType->setUpdatedAt($dateUpdated);
        $infoType->setStatus('on');
        $infoType->setCreatedBy($adminUser->getId());
        $infoType->setUpdatedBy($adminUser->getId());
       ;
       $manager->persist($infoType);
       $this->addReference(self::PREFIX . $i, $infoType);
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
