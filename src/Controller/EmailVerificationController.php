<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailVerificationController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }
    #[Route('/verify', name: 'app_verify_notice')]
    public function notice(): Response
    {
        return $this->render('email_verification/notice.html.twig');
    }

    #[Route('/verify/{token}', name: 'app_verify_email')]
    public function verify(
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $userRepository->findByVerificationToken($token);

        if (!$user) {
            $this->addFlash('error', $this->translator->trans('flash.verify_invalid_link'));

            return $this->redirectToRoute('app_home');
        }

        if ($user->isVerificationTokenExpired()) {
            $this->addFlash('error', $this->translator->trans('flash.verify_expired'));

            return $this->redirectToRoute('app_verify_notice');
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt(null);

        $entityManager->flush();

        $this->addFlash('success', $this->translator->trans('flash.verify_success'));

        return $this->redirectToRoute('app_login');
    }

    #[Route('/verify/resend', name: 'app_verify_resend')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function resend(
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($user->isVerified()) {
            $this->addFlash('info', $this->translator->trans('flash.verify_already'));

            return $this->redirectToRoute('app_home');
        }

        $token = $user->generateVerificationToken();
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
            $this->addFlash('error', $this->translator->trans('flash.verify_resend_failed'));

            return $this->redirectToRoute('app_verify_notice');
        }

        $this->addFlash('success', $this->translator->trans('flash.verify_resent'));

        return $this->redirectToRoute('app_verify_notice');
    }
}
