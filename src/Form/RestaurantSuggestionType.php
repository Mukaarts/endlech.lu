<?php

namespace App\Form;

use App\Entity\RestaurantSuggestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RestaurantSuggestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name des Restaurants',
                'attr' => ['placeholder' => 'z.B. Brasserie de la Poste'],
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
                'label' => 'Küche / Art',
                'attr' => ['placeholder' => 'z.B. Italienisch, Café, Brasserie'],
                'constraints' => [
                    new NotBlank(message: 'Bitte gib die Art des Restaurants ein.'),
                    new Length(max: 80, maxMessage: 'Die Küche darf maximal {{ limit }} Zeichen lang sein.'),
                ],
            ])
            ->add('emoji', TextType::class, [
                'label' => 'Emoji (optional)',
                'attr' => ['placeholder' => '🍽️'],
                'required' => false,
                'constraints' => [
                    new Length(max: 10, maxMessage: 'Das Emoji darf maximal {{ limit }} Zeichen lang sein.'),
                ],
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
            ->add('notes', TextareaType::class, [
                'label' => 'Weitere Hinweise zur Barrierefreiheit (optional)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'z.B. Eingang stufenlos, Parkplatz für Rollstuhlfahrer vorhanden ...',
                    'rows' => 4,
                ],
                'constraints' => [
                    new Length(max: 1000, maxMessage: 'Die Hinweise dürfen maximal {{ limit }} Zeichen lang sein.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RestaurantSuggestion::class,
        ]);
    }
}
