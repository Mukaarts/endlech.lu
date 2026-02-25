<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RegistrationController extends AbstractController
{
    #[Route('/registrieren', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()),
            );

            $token = $user->generateVerificationToken();

            $entityManager->persist($user);
            $entityManager->flush();

            $verifyUrl = $this->generateUrl('app_verify_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            $email = (new TemplatedEmail())
                ->from(new Address('noreply@endlech.lu', 'Endlech.lu'))
                ->to($user->getEmail())
                ->subject('Bestätige deine E-Mail-Adresse')
                ->htmlTemplate('email/verification.html.twig')
                ->context([
                    'user' => $user,
                    'verifyUrl' => $verifyUrl,
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Registrierung erfolgreich! Bitte überprüfe dein E-Mail-Postfach und bestätige deine Adresse.');

            return $this->redirectToRoute('app_verify_notice');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
