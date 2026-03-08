<?php

namespace App\Form;

use App\Entity\Restaurant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;

class RestaurantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['placeholder' => 'Name des Restaurants'],
                'constraints' => [
                    new NotBlank(message: 'Bitte gib den Namen ein.'),
                    new Length(max: 150, maxMessage: 'Der Name darf maximal {{ limit }} Zeichen lang sein.'),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Stadt',
                'attr' => ['placeholder' => 'z.B. Luxembourg-Ville'],
                'constraints' => [
                    new NotBlank(message: 'Bitte gib die Stadt ein.'),
                    new Length(max: 100, maxMessage: 'Die Stadt darf maximal {{ limit }} Zeichen lang sein.'),
                ],
            ])
            ->add('cuisine', TextType::class, [
                'label' => 'Küche',
                'attr' => ['placeholder' => 'z.B. Italienisch, Asiatisch'],
                'constraints' => [
                    new NotBlank(message: 'Bitte gib die Küche ein.'),
                    new Length(max: 80, maxMessage: 'Die Küche darf maximal {{ limit }} Zeichen lang sein.'),
                ],
            ])
            ->add('emoji', TextType::class, [
                'label' => 'Emoji',
                'attr' => ['placeholder' => '🍽️'],
                'constraints' => [
                    new NotBlank(message: 'Bitte gib ein Emoji ein.'),
                    new Length(max: 10, maxMessage: 'Das Emoji darf maximal {{ limit }} Zeichen lang sein.'),
                ],
            ])
            ->add('rating', NumberType::class, [
                'label' => 'Bewertung (0–10)',
                'required' => false,
                'html5' => true,
                'attr' => ['placeholder' => 'z.B. 8.5', 'step' => '0.1', 'min' => '0', 'max' => '10'],
                'constraints' => [
                    new Range(
                        min: 0,
                        max: 10,
                        notInRangeMessage: 'Die Bewertung muss zwischen {{ min }} und {{ max }} liegen.',
                    ),
                ],
            ])
            ->add('isOpen', CheckboxType::class, [
                'label' => 'Geöffnet',
                'required' => false,
            ])
            ->add('isWheelchairAccessible', CheckboxType::class, [
                'label' => 'Rollstuhlgerecht',
                'required' => false,
            ])
            ->add('hasAccessibleToilet', CheckboxType::class, [
                'label' => 'Barrierefreies WC',
                'required' => false,
            ])
            ->add('allowsAssistanceDogs', CheckboxType::class, [
                'label' => 'Assistenzhund erlaubt',
                'required' => false,
            ])
            ->add('hasBrightLighting', CheckboxType::class, [
                'label' => 'Helle Beleuchtung',
                'required' => false,
            ])
            ->add('acceptsCash', CheckboxType::class, [
                'label' => 'Barzahlung',
                'required' => false,
            ])
            ->add('acceptsCard', CheckboxType::class, [
                'label' => 'Kreditkarte / EC-Karte',
                'required' => false,
            ])
            ->add('acceptsPayconiq', CheckboxType::class, [
                'label' => 'Payconiq',
                'required' => false,
            ])
            ->add('accessibilityNotes', CollectionType::class, [
                'label' => 'Hinweise zur Barrierefreiheit',
                'entry_type' => TextType::class,
                'entry_options' => [
                    'attr' => ['placeholder' => 'z.B. ok:Eingang stufenlos oder warn:Stufe am Eingang'],
                    'constraints' => [
                        new NotBlank(message: 'Der Hinweis darf nicht leer sein.'),
                        new Regex(
                            pattern: '/^(ok|warn):.+$/',
                            message: 'Format: "ok:Text" oder "warn:Text"',
                        ),
                    ],
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Restaurant::class,
        ]);
    }
}
