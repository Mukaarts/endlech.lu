<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Repository\RestaurantRepository;
use App\Service\AvatarUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/profile')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly RestaurantRepository $restaurantRepository,
    ) {
    }

    #[Route('', name: 'app_profile', methods: ['GET'])]
    public function index(): Response
    {
        $profileForm = $this->createForm(ProfileType::class, $this->getUser(), [
            'action' => $this->generateUrl('app_profile_edit'),
        ]);

        $passwordForm = $this->createForm(ChangePasswordType::class, null, [
            'action' => $this->generateUrl('app_profile_password'),
        ]);

        return $this->render('profile/index.html.twig', [
            'profileForm' => $profileForm,
            'passwordForm' => $passwordForm,
            'submittedRestaurants' => $this->restaurantRepository->findBySubmitter($this->getUser()),
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit', methods: ['POST'])]
    public function edit(Request $request, EntityManagerInterface $em, AvatarUploadService $avatarService): Response
    {
        $user = $this->getUser();
        $profileForm = $this->createForm(ProfileType::class, $user);
        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $avatarFile = $profileForm->get('avatar')->getData();
            if ($avatarFile instanceof UploadedFile) {
                $avatarService->upload($avatarFile, $user);
            }

            $em->flush();

            $this->addFlash('success', $this->translator->trans('flash.profile_updated'));

            return $this->redirectToRoute('app_profile');
        }

        $passwordForm = $this->createForm(ChangePasswordType::class, null, [
            'action' => $this->generateUrl('app_profile_password'),
        ]);

        return $this->render('profile/index.html.twig', [
            'profileForm' => $profileForm,
            'passwordForm' => $passwordForm,
            'submittedRestaurants' => $this->restaurantRepository->findBySubmitter($this->getUser()),
        ]);
    }

    #[Route('/password', name: 'app_profile_password', methods: ['POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $passwordForm = $this->createForm(ChangePasswordType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $currentPassword = $passwordForm->get('currentPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', $this->translator->trans('flash.profile_wrong_password'));

                return $this->redirectToRoute('app_profile');
            }

            $newPassword = $passwordForm->get('newPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $em->flush();

            $this->addFlash('success', $this->translator->trans('flash.profile_password_changed'));

            return $this->redirectToRoute('app_profile');
        }

        $profileForm = $this->createForm(ProfileType::class, $user, [
            'action' => $this->generateUrl('app_profile_edit'),
        ]);

        return $this->render('profile/index.html.twig', [
            'profileForm' => $profileForm,
            'passwordForm' => $passwordForm,
            'submittedRestaurants' => $this->restaurantRepository->findBySubmitter($this->getUser()),
        ]);
    }

    #[Route('/avatar/delete', name: 'app_profile_avatar_delete', methods: ['POST'])]
    public function deleteAvatar(Request $request, AvatarUploadService $avatarService): Response
    {
        if ($this->isCsrfTokenValid('delete-avatar', $request->request->getString('_token'))) {
            $avatarService->delete($this->getUser());
            $this->addFlash('success', $this->translator->trans('flash.profile_avatar_deleted'));
        } else {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));
        }

        return $this->redirectToRoute('app_profile');
    }
}
