<?php

namespace App\DataFixtures;

use App\Entity\Advice;
use App\Entity\Month;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Générer les mois
        $faker = FakerFactory::create('fr_FR'); // French locale for month names
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $month = new Month();
            $month->setName($faker->monthName); // Generate random month names
            $month->setMonthNumber($i); // Assign the correct month number (1 to 12)

            $months[$i] = $month; // Store for later use if needed

            $manager->persist($month); // Persist to the database
        }

        for ($i = 0; $i < 30; $i++) {
            $advice = new Advice();
            $advice->setContent($faker->sentence);

            // Assign random months
            $randomMonths = array_rand($months, random_int(1, 12));
            if (!is_array($randomMonths)) {
                $randomMonths = [$randomMonths];
            }

            foreach ($randomMonths as $monthIndex) {
                $advice->addMonth($months[$monthIndex]);
            }
        }

        $manager->flush();
    }
}
