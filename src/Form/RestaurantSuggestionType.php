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
                'label' => 'form.suggestion_name',
                'attr' => ['placeholder' => 'form.suggestion_name_placeholder'],
                'constraints' => [
                    new NotBlank(message: 'suggestion.name_blank'),
                    new Length(max: 150, maxMessage: 'suggestion.name_max'),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'form.city',
                'attr' => ['placeholder' => 'form.city_placeholder'],
                'constraints' => [
                    new NotBlank(message: 'suggestion.city_blank'),
                    new Length(max: 100, maxMessage: 'suggestion.city_max'),
                ],
            ])
            ->add('cuisine', TextType::class, [
                'label' => 'form.suggestion_cuisine',
                'attr' => ['placeholder' => 'form.suggestion_cuisine_placeholder'],
                'constraints' => [
                    new NotBlank(message: 'suggestion.cuisine_blank'),
                    new Length(max: 80, maxMessage: 'suggestion.cuisine_max'),
                ],
            ])
            ->add('emoji', TextType::class, [
                'label' => 'form.suggestion_emoji',
                'attr' => ['placeholder' => 'form.emoji_placeholder'],
                'required' => false,
                'constraints' => [
                    new Length(max: 10, maxMessage: 'suggestion.emoji_max'),
                ],
            ])
            ->add('isWheelchairAccessible', CheckboxType::class, [
                'label' => 'form.wheelchair_accessible',
                'required' => false,
            ])
            ->add('hasAccessibleToilet', CheckboxType::class, [
                'label' => 'form.accessible_toilet',
                'required' => false,
            ])
            ->add('allowsAssistanceDogs', CheckboxType::class, [
                'label' => 'form.assistance_dogs',
                'required' => false,
            ])
            ->add('hasBrightLighting', CheckboxType::class, [
                'label' => 'form.bright_lighting',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'form.suggestion_notes',
                'required' => false,
                'attr' => [
                    'placeholder' => 'form.suggestion_notes_placeholder',
                    'rows' => 4,
                ],
                'constraints' => [
                    new Length(max: 1000, maxMessage: 'suggestion.notes_max'),
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
