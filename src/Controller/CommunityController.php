<?php

namespace App\Controller;

use App\Entity\RestaurantSuggestion;
use App\Form\RestaurantSuggestionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/community')]
final class CommunityController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[Route('/suggest', name: 'community_vorschlagen', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function vorschlagen(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user->isVerified()) {
            $this->addFlash('error', $this->translator->trans('flash.suggest_verify_first'));

            return $this->redirectToRoute('app_verify_notice');
        }

        $suggestion = new RestaurantSuggestion();
        $form = $this->createForm(RestaurantSuggestionType::class, $suggestion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $suggestion->setSuggestedBy($user);
            $entityManager->persist($suggestion);
            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('flash.suggest_success'));

            return $this->redirectToRoute('community_danke');
        }

        return $this->render('community/vorschlagen.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/thanks', name: 'community_danke')]
    public function danke(): Response
    {
        return $this->render('community/danke.html.twig');
    }
}
