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
                'label' => 'form.name',
                'attr' => ['placeholder' => 'form.name_placeholder', 'autocomplete' => 'name'],
                'constraints' => [
                    new NotBlank(message: 'user.name_blank'),
                    new Length(min: 2, max: 100, minMessage: 'user.name_min'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'form.email',
                'attr' => ['placeholder' => 'form.email_placeholder', 'autocomplete' => 'email'],
                'constraints' => [
                    new NotBlank(message: 'user.email_blank'),
                    new Email(message: 'user.email_invalid'),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'form.password',
                    'attr' => ['placeholder' => 'form.password_placeholder', 'autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'form.password_confirm',
                    'attr' => ['placeholder' => 'form.password_confirm_placeholder', 'autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'form.password_mismatch',
                'constraints' => [
                    new NotBlank(message: 'user.password_blank'),
                    new Length(
                        min: 8,
                        max: 4096,
                        minMessage: 'user.password_min',
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
