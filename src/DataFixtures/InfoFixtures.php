<?php

namespace App\DataFixtures;

use App\Entity\Info;
use App\Entity\InfoType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Faker\Factory;

class InfoFixtures extends Fixture implements DependentFixtureInterface
{
    public const PREFIX = "info#";
    public const POOL_MIN = 0;
    public const POOL_MAX = 10;
    private Generator $faker;
    public function __construct()
    {
        $this->faker = Factory::create("fr_FR");
    }
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTime();
        $prefixInfo = InfoTypeFixtures::PREFIX;

        $infoTypeRefs = [];
        for ($i = InfoTypeFixtures::POOL_MIN; $i < InfoTypeFixtures::POOL_MAX; $i++) {
            $infoTypeRefs[] = $prefixInfo . $i;
        }

        $clientRefs = [];
        for ($i = ClientFixtures::POOL_MIN; $i < ClientFixtures::POOL_MAX; $i++) {
            $clientRefs[] = ClientFixtures::PREFIX . $i;
        }

        $accountRefs = [];
        for ($i = AccountFixtures::POOL_MIN; $i < AccountFixtures::POOL_MAX; $i++) {
            $accountRefs[] = AccountFixtures::PREFIX . $i;
        }

        for ($i = self::POOL_MIN; $i < self::POOL_MAX; $i++) {
            $dateCreated = $this->faker->dateTimeInInterval('-1 year', '+1 year');
            $dateUpdated = $this->faker->dateTimeBetween($dateCreated, $now);
            $type = $this->getReference($infoTypeRefs[array_rand($infoTypeRefs, 1)]);
            $user = $this->getReference(UserFixtures::ADMIN_REF);

            $info = new Info();
            $info
                ->setAnonymous($this->faker->boolean(20))
                ->setInfo($this->faker->numerify('info-###'))
                ->setType($type)
                ->addClient($this->getReference($clientRefs[array_rand($clientRefs, 1)]))
                ->addAccount($this->getReference($accountRefs[array_rand($accountRefs, 1)]))
                ->setCreatedAt($dateCreated)
                ->setUpdatedAt($dateUpdated)
                ->setStatus($this->faker->boolean() ? "on" : "off")
                ->setUser($user);
            $manager->persist($info);
            $this->addReference(self::PREFIX . $i, $info);
        }

        $manager->flush();
    }
    
    public function getDependencies(): array
    {
        return [
            InfoTypeFixtures::class,
            ClientFixtures::class,
            AccountFixtures::class,
            UserFixtures::class
        ];
    }
}
