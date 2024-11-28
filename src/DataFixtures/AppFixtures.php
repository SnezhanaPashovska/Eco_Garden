<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Factory\AdviceFactory;
use App\Factory\MonthFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create(); // Initialize Faker
        // Création d'un user "normal"
        for ($i = 0; $i < 5; $i++) {
            $user = new User();
            $user->setEmail($faker->email());
            $user->setRoles(["ROLE_USER"]);
            $user->setPassword($this->userPasswordHasher->hashPassword($user, "password"));
            $user->setCity($faker->city());
            $manager->persist($user);
        }

        // Création d'un user admin
        $userAdmin = new User();
        $userAdmin->setEmail("admin@ecogarden.com");
        $userAdmin->setRoles(["ROLE_ADMIN"]);
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "password"));
        $userAdmin->setCity($faker->city());
        $manager->persist($userAdmin);

        // Generate months
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $month = MonthFactory::createSpecificMonth($i);
            $months[$i] = $month; // Stocker chaque mois dans un tableau indexé par son numéro
        }

        // generate advices
        $advices = AdviceFactory::createMany(50, function () use ($months) {
            $randomMonths = array_rand($months, random_int(1, 12));
            if (!is_array($randomMonths)) {
                $randomMonths = [$randomMonths];
            }

            return [
                'months' => array_map(fn($monthNumber) => $months[$monthNumber], $randomMonths),
            ];
        });

        $manager->flush();
    }
}
