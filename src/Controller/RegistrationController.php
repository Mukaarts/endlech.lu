<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RegistrationController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }
    #[Route('/register', name: 'app_register')]
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
                ->to($user->getEmail())
                ->subject($this->translator->trans('email.verify_subject'))
                ->htmlTemplate('email/verification.html.twig')
                ->context([
                    'user' => $user,
                    'verifyUrl' => $verifyUrl,
                ]);

            try {
                $mailer->send($email);
            } catch (TransportExceptionInterface) {
                $this->addFlash('warning', $this->translator->trans('flash.register_email_failed'));

                return $this->redirectToRoute('app_verify_notice');
            }

            $this->addFlash('success', $this->translator->trans('flash.register_success'));

            return $this->redirectToRoute('app_verify_notice');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
