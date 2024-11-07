<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Action;
use Faker\Factory;
use Faker\Generator;

class ActionFixtures extends Fixture
{ 
    public const PREFIX = "action#";
    public const actionNames = ['create', 'update', 'delete', 'read'];
    private Generator $faker;
    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }
    public function load(ObjectManager $manager): void
    { 
        $now = new \DateTime();

        
        foreach (self::actionNames as $name) {
            
            $dateCreated = $this->faker->dateTimeInInterval('-1 year', '+1 year');
            $dateUpdated = $this->faker->dateTimeBetween($dateCreated, $now);
            $action = new Action();
            $action
                ->setName($name)
                ->setCreatedAt($dateCreated)
                ->setUpdatedAt($dateUpdated)
                ->setStatus('on')
            ;
            $manager->persist($action);
            $this->addReference(self::PREFIX . $name, $action);
        }
        

        

        
    

        

        $manager->flush();
    }
}
