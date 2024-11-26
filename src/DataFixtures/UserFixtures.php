<?php

namespace App\DataFixtures;

use App\Entity\User;
use Faker\Factory;
use Faker\Generator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const PREFIX = "user#";
    public const POOL_MIN = 0;
    public const POOL_MAX = 10;
    public const ADMIN_REF = "adminUser";
    private Generator $faker;
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setUsername("admin");
        $admin->setRoles(["ROLE_ADMIN"]);
        $admin->setPassword($this->userPasswordHasher->hashPassword($admin, 'password'));

        $manager->persist($admin);
        $manager->flush();

        $this->addReference(self::ADMIN_REF, $admin);
    }
}
