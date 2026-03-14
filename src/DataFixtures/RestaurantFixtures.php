<?php

namespace App\DataFixtures;

use App\Entity\OrderingOption;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\Language;
use App\Enum\OrderingPlatform;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RestaurantFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }

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
                'hasChangingTable'       => true,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => true,
                'isVegan'                => false,
                'isVegetarian'           => true,
                'isHalal'                => false,
                'accessibilityNotes'     => ['ok:Eingang stufenlos', 'ok:WC Tür > 90cm'],
                'isVerified'             => true,
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR, Language::EN, Language::OTHER],
                'phone'                  => '+352 26 12 34 56',
                'email'                  => 'info@bellavista.lu',
                'website'                => 'https://www.bellavista.lu',
                'instagramUrl'           => 'https://instagram.com/bellavista.lu',
                'facebookUrl'            => 'https://facebook.com/bellavista.lu',
                'orderingOptions'        => [
                    [OrderingPlatform::UBER_EATS, 'https://www.ubereats.com/lu/store/pizzeria-bella-vista'],
                    [OrderingPlatform::WEBSITE, 'https://www.bellavista.lu'],
                    [OrderingPlatform::PHONE, '+352 26 12 34 56'],
                ],
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
                'hasChangingTable'       => false,
                'acceptsCash'            => true,
                'acceptsCard'            => false,
                'acceptsPayconiq'        => false,
                'isVegan'                => false,
                'isVegetarian'           => true,
                'isHalal'                => false,
                'accessibilityNotes'     => ['ok:Menü in Braille', 'warn:Stufe am Eingang'],
                'spokenLanguages'        => [Language::EN, Language::FR],
                'phone'                  => '+352 27 44 55 66',
                'instagramUrl'           => 'https://instagram.com/umamicorner',
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
                'hasChangingTable'       => true,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => false,
                'isVegan'                => false,
                'isVegetarian'           => false,
                'isHalal'                => true,
                'accessibilityNotes'     => ['ok:Parkplatz vor der Tür'],
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR],
                'phone'                  => '+352 27 98 76 54',
                'email'                  => 'hello@burgerandco.lu',
                'website'                => 'https://www.burgerandco.lu',
                'instagramUrl'           => 'https://instagram.com/burgerandco.lu',
                'facebookUrl'            => 'https://facebook.com/burgerandco.lu',
                'tiktokUrl'              => 'https://tiktok.com/@burgerandco.lu',
                'orderingOptions'        => [
                    [OrderingPlatform::JUST_EAT, 'https://www.just-eat.lu/menu/burger-and-co'],
                    [OrderingPlatform::PHONE, '+352 27 98 76 54'],
                ],
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
                'hasChangingTable'       => true,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => true,
                'isVegan'                => true,
                'isVegetarian'           => true,
                'isHalal'                => false,
                'accessibilityNotes'     => ['ok:Eingang stufenlos', 'ok:Barrierefreies WC', 'ok:Assistenzhunde willkommen'],
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR, Language::EN],
                'phone'                  => '+352 26 88 99 00',
                'email'                  => 'reservierung@lejardin.lu',
                'website'                => 'https://www.lejardin.lu',
                'facebookUrl'            => 'https://facebook.com/lejardinbrasserie',
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
                'hasChangingTable'       => false,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => false,
                'isVegan'                => false,
                'isVegetarian'           => false,
                'isHalal'                => true,
                'accessibilityNotes'     => ['warn:Zwei Stufen am Eingang', 'ok:Helle Innenbeleuchtung'],
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR],
                'phone'                  => '+352 75 11 22 33',
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
                'hasChangingTable'       => false,
                'acceptsCash'            => true,
                'acceptsCard'            => false,
                'acceptsPayconiq'        => false,
                'isVegan'                => true,
                'isVegetarian'           => true,
                'isHalal'                => false,
                'accessibilityNotes'     => ['ok:Ebenerdiger Zugang', 'ok:Assistenzhunde erlaubt', 'warn:WC nicht barrierefrei'],
                'spokenLanguages'        => [Language::LU, Language::DE],
                'email'                  => 'hallo@cafenordstad.lu',
                'instagramUrl'           => 'https://instagram.com/cafenordstad',
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
                'hasChangingTable'       => true,
                'acceptsCash'            => false,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => true,
                'isVegan'                => false,
                'isVegetarian'           => true,
                'isHalal'                => false,
                'accessibilityNotes'     => ['ok:Vollständig barrierefrei', 'ok:Rollstuhlrampe vorhanden', 'ok:Barrierefreies WC'],
                'isVerified'             => true,
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR, Language::EN],
                'phone'                  => '+352 26 33 44 55',
                'email'                  => 'info@sushizen.lu',
                'website'                => 'https://www.sushizen.lu',
                'instagramUrl'           => 'https://instagram.com/sushizen.lu',
                'facebookUrl'            => 'https://facebook.com/sushizen.lu',
                'orderingOptions'        => [
                    [OrderingPlatform::DELIVEROO, 'https://deliveroo.lu/menu/luxembourg/sushi-zen'],
                    [OrderingPlatform::JUST_EAT, 'https://www.just-eat.lu/menu/sushi-zen'],
                ],
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
                'hasChangingTable'       => false,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => false,
                'isVegan'                => false,
                'isVegetarian'           => false,
                'isHalal'                => false,
                'accessibilityNotes'     => ['warn:Kopfsteinpflaster vor dem Eingang', 'warn:Treppen im Inneren'],
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR],
                'phone'                  => '+352 23 66 77 88',
                'website'                => 'https://www.wainhaus.lu',
                'facebookUrl'            => 'https://facebook.com/wainhausammarkt',
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
                'hasChangingTable'       => false,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => true,
                'isVegan'                => false,
                'isVegetarian'           => true,
                'isHalal'                => false,
                'accessibilityNotes'     => ['ok:Ebenerdiger Eingang', 'ok:Helle Beleuchtung im Gastraum'],
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR, Language::PT, Language::OTHER],
                'phone'                  => '+352 81 22 33 44',
                'email'                  => 'ciao@trattoriaroma.lu',
                'website'                => 'https://www.trattoriaroma.lu',
                'instagramUrl'           => 'https://instagram.com/trattoriaroma.lu',
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
                'hasChangingTable'       => true,
                'acceptsCash'            => false,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => true,
                'isVegan'                => true,
                'isVegetarian'           => true,
                'isHalal'                => false,
                'accessibilityNotes'     => ['ok:Vollständig barrierefrei', 'ok:Induktive Höranlage vorhanden'],
                'isVerified'             => true,
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR, Language::EN, Language::PT],
                'email'                  => 'hello@greenbowl.lu',
                'website'                => 'https://www.greenbowl.lu',
                'instagramUrl'           => 'https://instagram.com/greenbowl.lu',
                'facebookUrl'            => 'https://facebook.com/greenbowl.lu',
                'tiktokUrl'              => 'https://tiktok.com/@greenbowl.lu',
                'orderingOptions'        => [
                    [OrderingPlatform::UBER_EATS, 'https://www.ubereats.com/lu/store/green-bowl'],
                    [OrderingPlatform::WEBSITE, 'https://www.greenbowl.lu/order'],
                ],
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
                'hasChangingTable'       => false,
                'acceptsCash'            => true,
                'acceptsCard'            => false,
                'acceptsPayconiq'        => false,
                'isVegan'                => false,
                'isVegetarian'           => false,
                'isHalal'                => false,
                'accessibilityNotes'     => ['warn:Historisches Gebäude, mehrere Stufen', 'warn:Keine barrierefreie Toilette'],
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR],
                'phone'                  => '+352 46 11 22 33',
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
            $restaurant->setHasChangingTable($data['hasChangingTable']);
            $restaurant->setAcceptsCash($data['acceptsCash']);
            $restaurant->setAcceptsCard($data['acceptsCard']);
            $restaurant->setAcceptsPayconiq($data['acceptsPayconiq']);
            $restaurant->setIsVegan($data['isVegan']);
            $restaurant->setIsVegetarian($data['isVegetarian']);
            $restaurant->setIsHalal($data['isHalal']);
            $restaurant->setAccessibilityNotes($data['accessibilityNotes']);
            $restaurant->setSpokenLanguages($data['spokenLanguages'] ?? []);
            $restaurant->setPhone($data['phone'] ?? null);
            $restaurant->setEmail($data['email'] ?? null);
            $restaurant->setWebsite($data['website'] ?? null);
            $restaurant->setInstagramUrl($data['instagramUrl'] ?? null);
            $restaurant->setFacebookUrl($data['facebookUrl'] ?? null);
            $restaurant->setTiktokUrl($data['tiktokUrl'] ?? null);

            $isVerified = $data['isVerified'] ?? false;
            $restaurant->setIsVerified($isVerified);
            if ($isVerified) {
                $restaurant->setVerifiedAt(new \DateTimeImmutable('2026-01-15'));
                $restaurant->setVerifiedBy($this->getReference(UserFixtures::REFERENCE_ADMIN, User::class));
            }

            foreach ($data['orderingOptions'] ?? [] as [$platform, $url]) {
                $option = new OrderingOption();
                $option->setPlatform($platform);
                $option->setUrl($url);
                $restaurant->addOrderingOption($option);
            }

            $manager->persist($restaurant);
        }

        $manager->flush();
    }
}
