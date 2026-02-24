<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['placeholder' => 'Dein Name', 'autocomplete' => 'name'],
                'constraints' => [
                    new NotBlank(message: 'Bitte gib deinen Namen ein.'),
                    new Length(min: 2, max: 100, minMessage: 'Der Name muss mindestens {{ limit }} Zeichen lang sein.'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-Mail-Adresse',
                'attr' => ['placeholder' => 'deine@email.lu', 'autocomplete' => 'email'],
                'constraints' => [
                    new NotBlank(message: 'Bitte gib deine E-Mail-Adresse ein.'),
                    new Email(message: 'Bitte gib eine gültige E-Mail-Adresse ein.'),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Passwort',
                    'attr' => ['placeholder' => 'Mindestens 8 Zeichen', 'autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Passwort bestätigen',
                    'attr' => ['placeholder' => 'Passwort wiederholen', 'autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Die Passwörter stimmen nicht überein.',
                'constraints' => [
                    new NotBlank(message: 'Bitte gib ein Passwort ein.'),
                    new Length(
                        min: 8,
                        max: 4096,
                        minMessage: 'Das Passwort muss mindestens {{ limit }} Zeichen lang sein.',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
