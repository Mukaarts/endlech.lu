<?php

namespace App\DataFixtures;

use App\Entity\Restaurant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RestaurantFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $restaurants = [
            [
                'name'                   => 'Pizzeria Bella Vista',
                'city'                   => 'Luxembourg-Ville',
                'cuisine'                => 'Italienisch',
                'emoji'                  => '🍕',
                'rating'                 => 9.8,
                'isOpen'                 => true,
                'isWheelchairAccessible' => true,
                'hasAccessibleToilet'    => true,
                'allowsAssistanceDogs'   => true,
                'hasBrightLighting'      => false,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => true,
                'accessibilityNotes'     => ['ok:Eingang stufenlos', 'ok:WC Tür > 90cm'],
            ],
            [
                'name'                   => 'Umami Corner',
                'city'                   => 'Esch-Belval',
                'cuisine'                => 'Asiatisch',
                'emoji'                  => '🍜',
                'rating'                 => null,
                'isOpen'                 => false,
                'isWheelchairAccessible' => false,
                'hasAccessibleToilet'    => false,
                'allowsAssistanceDogs'   => true,
                'hasBrightLighting'      => true,
                'acceptsCash'            => true,
                'acceptsCard'            => false,
                'acceptsPayconiq'        => false,
                'accessibilityNotes'     => ['ok:Menü in Braille', 'warn:Stufe am Eingang'],
            ],
            [
                'name'                   => 'Burger & Co.',
                'city'                   => 'Dudelange',
                'cuisine'                => 'Fast Food',
                'emoji'                  => '🍔',
                'rating'                 => 8.5,
                'isOpen'                 => true,
                'isWheelchairAccessible' => true,
                'hasAccessibleToilet'    => false,
                'allowsAssistanceDogs'   => false,
                'hasBrightLighting'      => true,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => false,
                'accessibilityNotes'     => ['ok:Parkplatz vor der Tür'],
            ],
            [
                'name'                   => 'Le Jardin Brasserie',
                'city'                   => 'Kirchberg',
                'cuisine'                => 'Französisch',
                'emoji'                  => '🥂',
                'rating'                 => 9.1,
                'isOpen'                 => true,
                'isWheelchairAccessible' => true,
                'hasAccessibleToilet'    => true,
                'allowsAssistanceDogs'   => true,
                'hasBrightLighting'      => false,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => true,
                'accessibilityNotes'     => ['ok:Eingang stufenlos', 'ok:Barrierefreies WC', 'ok:Assistenzhunde willkommen'],
            ],
            [
                'name'                   => 'Steakhaus Moselle',
                'city'                   => 'Grevenmacher',
                'cuisine'                => 'Steak & Grill',
                'emoji'                  => '🥩',
                'rating'                 => 8.0,
                'isOpen'                 => true,
                'isWheelchairAccessible' => false,
                'hasAccessibleToilet'    => false,
                'allowsAssistanceDogs'   => false,
                'hasBrightLighting'      => true,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => false,
                'accessibilityNotes'     => ['warn:Zwei Stufen am Eingang', 'ok:Helle Innenbeleuchtung'],
            ],
            [
                'name'                   => 'Café Nordstad',
                'city'                   => 'Diekirch',
                'cuisine'                => 'Café & Bistro',
                'emoji'                  => '☕',
                'rating'                 => 7.4,
                'isOpen'                 => false,
                'isWheelchairAccessible' => true,
                'hasAccessibleToilet'    => false,
                'allowsAssistanceDogs'   => true,
                'hasBrightLighting'      => true,
                'acceptsCash'            => true,
                'acceptsCard'            => false,
                'acceptsPayconiq'        => false,
                'accessibilityNotes'     => ['ok:Ebenerdiger Zugang', 'ok:Assistenzhunde erlaubt', 'warn:WC nicht barrierefrei'],
            ],
            [
                'name'                   => 'Sushi Zen',
                'city'                   => 'Strassen',
                'cuisine'                => 'Japanisch',
                'emoji'                  => '🍣',
                'rating'                 => 9.4,
                'isOpen'                 => true,
                'isWheelchairAccessible' => true,
                'hasAccessibleToilet'    => true,
                'allowsAssistanceDogs'   => false,
                'hasBrightLighting'      => false,
                'acceptsCash'            => false,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => true,
                'accessibilityNotes'     => ['ok:Vollständig barrierefrei', 'ok:Rollstuhlrampe vorhanden', 'ok:Barrierefreies WC'],
            ],
            [
                'name'                   => 'Wäinhaus am Markt',
                'city'                   => 'Remich',
                'cuisine'                => 'Luxemburgisch',
                'emoji'                  => '🍷',
                'rating'                 => 8.8,
                'isOpen'                 => true,
                'isWheelchairAccessible' => false,
                'hasAccessibleToilet'    => false,
                'allowsAssistanceDogs'   => false,
                'hasBrightLighting'      => false,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => false,
                'accessibilityNotes'     => ['warn:Kopfsteinpflaster vor dem Eingang', 'warn:Treppen im Inneren'],
            ],
            [
                'name'                   => 'Trattoria Roma',
                'city'                   => 'Ettelbruck',
                'cuisine'                => 'Italienisch',
                'emoji'                  => '🍝',
                'rating'                 => 8.2,
                'isOpen'                 => true,
                'isWheelchairAccessible' => true,
                'hasAccessibleToilet'    => false,
                'allowsAssistanceDogs'   => true,
                'hasBrightLighting'      => true,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => true,
                'accessibilityNotes'     => ['ok:Ebenerdiger Eingang', 'ok:Helle Beleuchtung im Gastraum'],
            ],
            [
                'name'                   => 'Green Bowl',
                'city'                   => 'Cloche d\'Or',
                'cuisine'                => 'Vegetarisch & Vegan',
                'emoji'                  => '🥗',
                'rating'                 => 9.0,
                'isOpen'                 => true,
                'isWheelchairAccessible' => true,
                'hasAccessibleToilet'    => true,
                'allowsAssistanceDogs'   => true,
                'hasBrightLighting'      => true,
                'acceptsCash'            => false,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => true,
                'accessibilityNotes'     => ['ok:Vollständig barrierefrei', 'ok:Induktive Höranlage vorhanden'],
            ],
            [
                'name'                   => 'Brasserie du Grund',
                'city'                   => 'Luxembourg-Grund',
                'cuisine'                => 'Luxemburgisch',
                'emoji'                  => '🍺',
                'rating'                 => 7.9,
                'isOpen'                 => false,
                'isWheelchairAccessible' => false,
                'hasAccessibleToilet'    => false,
                'allowsAssistanceDogs'   => false,
                'hasBrightLighting'      => false,
                'acceptsCash'            => true,
                'acceptsCard'            => false,
                'acceptsPayconiq'        => false,
                'accessibilityNotes'     => ['warn:Historisches Gebäude, mehrere Stufen', 'warn:Keine barrierefreie Toilette'],
            ],
        ];

        foreach ($restaurants as $data) {
            $restaurant = new Restaurant();
            $restaurant->setName($data['name']);
            $restaurant->setCity($data['city']);
            $restaurant->setCuisine($data['cuisine']);
            $restaurant->setEmoji($data['emoji']);
            $restaurant->setRating($data['rating']);
            $restaurant->setIsOpen($data['isOpen']);
            $restaurant->setIsWheelchairAccessible($data['isWheelchairAccessible']);
            $restaurant->setHasAccessibleToilet($data['hasAccessibleToilet']);
            $restaurant->setAllowsAssistanceDogs($data['allowsAssistanceDogs']);
            $restaurant->setHasBrightLighting($data['hasBrightLighting']);
            $restaurant->setAcceptsCash($data['acceptsCash']);
            $restaurant->setAcceptsCard($data['acceptsCard']);
            $restaurant->setAcceptsPayconiq($data['acceptsPayconiq']);
            $restaurant->setAccessibilityNotes($data['accessibilityNotes']);
            $manager->persist($restaurant);
        }

        $manager->flush();
    }
}
