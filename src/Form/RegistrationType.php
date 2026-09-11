<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
                    // ⚠ BF-119: `VALIDATION_MODE_STRICT` statt des HTML5-Defaults.
                    // Der Default lässt Adressen durch, die `Mime\Address` nach
                    // RFC 2822 ablehnt (`../../etc/passwd@example.lu`) — der Versand
                    // wirft dann eine `RfcComplianceException`, und weil vor dem
                    // Versand gespeichert wird, bleibt die Zeile stehen: 500er plus
                    // Datensatz. Gemessen an Feature 08, wo STRICT schon steht:
                    // acht realistische Adressen unverändert akzeptiert, zusätzlich
                    // abgelehnt werden nur Local-Parts über 64 Zeichen (die RFC 5321
                    // ohnehin verbietet). Umgekehrt akzeptiert STRICT
                    // `jean-luc@télécom.lu`, das der Default still abwies.
                    new Email(message: 'user.email_invalid', mode: Email::VALIDATION_MODE_STRICT),
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
            ])
            // Werbe-Einwilligung (Feature 04) – wie `plainPassword` nicht gemappt:
            // Die Entity speichert den Zeitpunkt (marketingConsentAt), nicht das
            // Häkchen selbst. Den Zeitpunkt setzt der Controller.
            //
            // ⚠ AK-03, Koppelungsverbot (Art. 7 Abs. 4 DSGVO): bewusst OHNE
            // IsTrue-Constraint und mit required: false. Die Einwilligung darf
            // keine Bedingung für die Registrierung sein – bleibt das Feld leer,
            // läuft die Anmeldung unverändert durch. Ein Zwang machte jede
            // Einwilligung in dieser Liste unwirksam.
            //
            // ⚠ AK-02: keine Vorbelegung. Kein 'data' => true – ein
            // vorangehaktes Kästchen ist keine Einwilligung.
            ->add('marketingConsent', CheckboxType::class, [
                'label' => 'marketing.consent.label',
                'help' => 'marketing.consent.help',
                'mapped' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
