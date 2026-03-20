<?php

namespace App\DataFixtures;

use App\Entity\Cuisine;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;

class CuisineFixtures extends Fixture
{
    public const CUISINES = [
        'Italienisch',
        'Französisch',
        'Asiatisch',
        'Japanisch',
        'Chinesisch',
        'Indisch',
        'Mexikanisch',
        'Amerikanisch',
        'Mediterran',
        'Luxemburgisch',
        'Portugiesisch',
        'Thai',
        'Vegetarisch',
        'Vegan',
        'Fast Food',
        'Steak & Grill',
        'Café & Bistro',
        'Sushi',
        'Pizza',
        'Burger',
    ];

    public function load(ObjectManager $manager): void
    {
        $slugger = new AsciiSlugger();

        foreach (self::CUISINES as $name) {
            $cuisine = new Cuisine();
            $cuisine->setName($name);
            $cuisine->setSlug(strtolower((string) $slugger->slug($name)));

            $manager->persist($cuisine);
            $this->addReference('cuisine_' . strtolower((string) $slugger->slug($name)), $cuisine);
        }

        $manager->flush();
    }
}
