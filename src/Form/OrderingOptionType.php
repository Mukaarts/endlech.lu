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
                'label' => 'Plattform',
                'choices' => OrderingPlatform::cases(),
                'choice_value' => fn (?OrderingPlatform $p) => $p?->value,
                'choice_label' => fn (OrderingPlatform $p) => $p->emoji().' '.$p->label(),
                'placeholder' => 'Plattform wählen...',
                'constraints' => [
                    new NotBlank(message: 'Bitte wähle eine Plattform.'),
                ],
            ])
            ->add('url', TextType::class, [
                'label' => 'URL / Telefonnummer',
                'attr' => ['placeholder' => 'https://... oder +352...'],
                'constraints' => [
                    new NotBlank(message: 'Bitte gib eine URL oder Telefonnummer ein.'),
                    new Length(max: 500, maxMessage: 'Die URL darf maximal {{ limit }} Zeichen lang sein.'),
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
