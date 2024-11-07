<?php

namespace App\DataFixtures;

use App\Entity\History;
use App\enum\EAction;
use App\enum\EService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Faker\Factory;

class HistoryFixtures extends Fixture implements DependentFixtureInterface
{
    public const PREFIX = "history#";
    public const POOL_MIN = 0;
    public const POOL_MAX = 10;
    private Generator $faker;
    public function __construct()
    {
        $this->faker = Factory::create("fr_FR");
    }
    public function load(ObjectManager $manager): void
    {
        $actionPrefix = ActionFixtures::PREFIX;
        $actionRefs = [];
        foreach (EAction::cases() as $action) {
            $actionRefs[] = $actionPrefix . $action->name;
        }

        $servicePrefix = ServiceFixtures::PREFIX;
        $serviceRefs = [];
        foreach (EService::cases() as $service) {
            $serviceRefs[] = $servicePrefix . $service->value;
        }

        for ($i = self::POOL_MIN; $i < self::POOL_MAX; $i++) {
            $dateCreated = $this->faker->dateTimeInInterval('-1 year', '+1 year');
            $history = new History();
            $action = $this->getReference($actionRefs[array_rand($actionRefs, 1)]);
            $service = $this->getReference($serviceRefs[array_rand($serviceRefs, 1)]);

            $history->setCreatedAt($dateCreated);
            $history->setStatus('on');
            $history->setAction($action);
            $history->setService($service);
            $manager->persist($history);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            ActionFixtures::class,
            ServiceFixtures::class,
        ];
    }
}
