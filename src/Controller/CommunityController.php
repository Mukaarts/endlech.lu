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

#[Route('/community')]
final class CommunityController extends AbstractController
{
    #[Route('/vorschlagen', name: 'community_vorschlagen', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function vorschlagen(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user->isVerified()) {
            $this->addFlash('error', 'Bitte bestätige zuerst deine E-Mail-Adresse, um Restaurants vorschlagen zu können.');

            return $this->redirectToRoute('app_verify_notice');
        }

        $suggestion = new RestaurantSuggestion();
        $form = $this->createForm(RestaurantSuggestionType::class, $suggestion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $suggestion->setSuggestedBy($user);
            $entityManager->persist($suggestion);
            $entityManager->flush();

            $this->addFlash('success', 'Danke für deinen Vorschlag! Wir prüfen ihn so bald wie möglich.');

            return $this->redirectToRoute('community_danke');
        }

        return $this->render('community/vorschlagen.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/danke', name: 'community_danke')]
    public function danke(): Response
    {
        return $this->render('community/danke.html.twig');
    }
}
