<?php

namespace App\Form;

use App\Entity\RestaurantSuggestion;
use App\Enum\Language;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

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
            ->add('hasChangingTable', CheckboxType::class, [
                'label' => 'form.changing_table',
                'required' => false,
            ])
            ->add('hasDisabledParking', CheckboxType::class, [
                'label' => 'form.disabled_parking',
                'required' => false,
            ])
            ->add('acceptsCash', CheckboxType::class, [
                'label' => 'form.accepts_cash',
                'required' => false,
            ])
            ->add('acceptsCard', CheckboxType::class, [
                'label' => 'form.accepts_card',
                'required' => false,
            ])
            ->add('acceptsPayconiq', CheckboxType::class, [
                'label' => 'form.accepts_payconiq',
                'required' => false,
            ])
            ->add('isVegan', CheckboxType::class, [
                'label' => 'form.vegan',
                'required' => false,
            ])
            ->add('isVegetarian', CheckboxType::class, [
                'label' => 'form.vegetarian',
                'required' => false,
            ])
            ->add('isHalal', CheckboxType::class, [
                'label' => 'form.halal',
                'required' => false,
            ])
            ->add('spokenLanguages', ChoiceType::class, [
                'label' => 'form.spoken_languages',
                'choices' => Language::cases(),
                'choice_value' => fn (?Language $l) => $l?->value,
                'choice_label' => fn (Language $l) => $l->flag() . ' ' . $l->label(),
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('phone', TelType::class, [
                'label' => 'form.phone',
                'required' => false,
                'attr' => ['placeholder' => 'form.phone_placeholder'],
                'constraints' => [
                    new Length(max: 30, maxMessage: 'suggestion.phone_max'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'form.email_field',
                'required' => false,
                'attr' => ['placeholder' => 'form.email_field_placeholder'],
                'constraints' => [
                    new Email(message: 'suggestion.email_invalid'),
                    new Length(max: 180, maxMessage: 'suggestion.email_max'),
                ],
            ])
            ->add('website', UrlType::class, [
                'label' => 'form.website',
                'required' => false,
                'attr' => ['placeholder' => 'form.website_placeholder'],
                'constraints' => [
                    new Url(message: 'suggestion.url_invalid'),
                    new Length(max: 500, maxMessage: 'suggestion.url_max'),
                ],
            ])
            ->add('instagramUrl', UrlType::class, [
                'label' => 'form.instagram',
                'required' => false,
                'attr' => ['placeholder' => 'form.instagram_placeholder'],
                'constraints' => [
                    new Url(message: 'suggestion.url_invalid'),
                    new Length(max: 500, maxMessage: 'suggestion.url_max'),
                ],
            ])
            ->add('facebookUrl', UrlType::class, [
                'label' => 'form.facebook',
                'required' => false,
                'attr' => ['placeholder' => 'form.facebook_placeholder'],
                'constraints' => [
                    new Url(message: 'suggestion.url_invalid'),
                    new Length(max: 500, maxMessage: 'suggestion.url_max'),
                ],
            ])
            ->add('tiktokUrl', UrlType::class, [
                'label' => 'form.tiktok',
                'required' => false,
                'attr' => ['placeholder' => 'form.tiktok_placeholder'],
                'constraints' => [
                    new Url(message: 'suggestion.url_invalid'),
                    new Length(max: 500, maxMessage: 'suggestion.url_max'),
                ],
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
