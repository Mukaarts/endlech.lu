<?php

namespace App\Form;

use App\Entity\OrderingOption;
use App\Enum\OrderingPlatform;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderingOptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('platform', ChoiceType::class, [
                'label' => 'form.platform',
                'choices' => OrderingPlatform::cases(),
                'choice_value' => fn (OrderingPlatform|string|null $p) => $p instanceof OrderingPlatform ? $p->value : $p,
                'choice_label' => fn (OrderingPlatform|string $p) => $p instanceof OrderingPlatform ? $p->emoji().' '.$p->label() : $p,
                'placeholder' => 'form.platform_placeholder',
                'constraints' => [
                    new NotBlank(message: 'ordering.platform_blank'),
                ],
            ])
            ->add('url', TextType::class, [
                'label' => 'form.url',
                'attr' => ['placeholder' => 'form.url_placeholder'],
                'constraints' => [
                    new NotBlank(message: 'ordering.url_blank'),
                    new Length(max: 500, maxMessage: 'ordering.url_max'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderingOption::class,
        ]);
    }
}
