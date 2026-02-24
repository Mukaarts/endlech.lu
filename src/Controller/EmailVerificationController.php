<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class EmailVerificationController extends AbstractController
{
    #[Route('/verifizieren', name: 'app_verify_notice')]
    public function notice(): Response
    {
        return $this->render('email_verification/notice.html.twig');
    }

    #[Route('/verifizieren/{token}', name: 'app_verify_email')]
    public function verify(
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $userRepository->findByVerificationToken($token);

        if (!$user) {
            $this->addFlash('error', 'Ungültiger Bestätigungslink.');

            return $this->redirectToRoute('app_home');
        }

        if ($user->isVerificationTokenExpired()) {
            $this->addFlash('error', 'Der Bestätigungslink ist abgelaufen. Bitte fordere einen neuen an.');

            return $this->redirectToRoute('app_verify_notice');
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt(null);

        $entityManager->flush();

        $this->addFlash('success', 'Deine E-Mail-Adresse wurde erfolgreich bestätigt! Du kannst dich jetzt anmelden.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/verifizieren-erneut', name: 'app_verify_resend')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function resend(
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($user->isVerified()) {
            $this->addFlash('info', 'Deine E-Mail-Adresse ist bereits bestätigt.');

            return $this->redirectToRoute('app_home');
        }

        $token = $user->generateVerificationToken();
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

        $this->addFlash('success', 'Eine neue Bestätigungs-E-Mail wurde gesendet.');

        return $this->redirectToRoute('app_verify_notice');
    }
}
