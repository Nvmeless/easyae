<?php

namespace App\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker\Factory;
use Faker\Generator;
use App\Entity\Contact;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class ContactFixtures extends Fixture implements DependentFixtureInterface
{

    public const PREFIX = "contact#";
    public const POOL_MIN = 0;
    public const POOL_MAX = 10;

    private Generator $faker;
    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }
    public function load(ObjectManager $manager): void
    {
        // $contactLinkRefs = [];
        // for ($i = ContactLinkFixtures::POOL_MIN; $i < ContactLinkFixtures::POOL_MAX; $i++) {
        //     $contactLinkRefs[] = ContactLinkFixtures::PREFIX . $i;
        // }
        $adminUser = $this->getReference(UserFixtures::ADMIN_REF);

        $fonctionRefs = [];
        for ($i = FonctionFixtures::POOL_MIN; $i < FonctionFixtures::POOL_MAX; $i++) {
            $fonctionRefs[] = FonctionFixtures::PREFIX . $i;
        }

        // $contactLinkCount = count($contactLinkRefs);
        $fonctionCount = count($fonctionRefs);

        for ($i = self::POOL_MIN; $i < self::POOL_MAX; $i++) {
            $contact = new Contact();
            $dateCreated = $this->faker->dateTimeBetween('-2 years', 'now');
            $user = $this->getReference(UserFixtures::ADMIN_REF);

            $contact->setName($this->faker->firstName(null))
                ->setCreatedAt($dateCreated)
                ->setUpdatedAt(new \DateTime())
                ->setStatus('on')
                ->setCreatedBy($adminUser->getId())
              ->setUser($user);
                ->setUpdatedBy($adminUser->getId());

            // if ($contactLinkCount > 0) {
            //     $linkIndex = min($i, $contactLinkCount - 1);
            //     $link = $this->getReference($contactLinkRefs[$linkIndex]);
            //     $contact->addLink($link);
            // }

            if ($fonctionCount > 0) {
                $fonctionIndex = min($i, $fonctionCount - 1);
                $fonction = $this->getReference($fonctionRefs[$fonctionIndex]);
                $contact->addFonction($fonction);
            }

            $manager->persist($contact);
            $this->addReference(self::PREFIX . $i, $contact);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            FonctionFixtures::class
        ];
    }
}