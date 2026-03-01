<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Form\RestaurantType;
use App\Repository\RestaurantRepository;
use App\Repository\RestaurantSuggestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminRestaurantController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function dashboard(RestaurantRepository $restaurantRepository, RestaurantSuggestionRepository $suggestionRepository): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'restaurantCount' => $restaurantRepository->count(),
            'pendingSuggestionCount' => $suggestionRepository->countPending(),
        ]);
    }

    #[Route('/restaurants', name: 'admin_restaurant_index')]
    public function index(RestaurantRepository $restaurantRepository): Response
    {
        return $this->render('admin/restaurant/index.html.twig', [
            'restaurants' => $restaurantRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/restaurants/neu', name: 'admin_restaurant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $restaurant = new Restaurant();
        $form = $this->createForm(RestaurantType::class, $restaurant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($restaurant);
            $entityManager->flush();

            $this->addFlash('success', 'Restaurant erfolgreich erstellt.');

            return $this->redirectToRoute('admin_restaurant_index');
        }

        return $this->render('admin/restaurant/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/restaurants/{id}/bearbeiten', name: 'admin_restaurant_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Restaurant $restaurant, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RestaurantType::class, $restaurant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Restaurant erfolgreich aktualisiert.');

            return $this->redirectToRoute('admin_restaurant_index');
        }

        return $this->render('admin/restaurant/edit.html.twig', [
            'restaurant' => $restaurant,
            'form' => $form,
        ]);
    }

    #[Route('/restaurants/{id}/loeschen', name: 'admin_restaurant_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Restaurant $restaurant, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete-restaurant-' . $restaurant->getId(), $request->request->getString('_token'))) {
            $entityManager->remove($restaurant);
            $entityManager->flush();

            $this->addFlash('success', 'Restaurant erfolgreich gelöscht.');
        } else {
            $this->addFlash('error', 'Ungültiges CSRF-Token. Bitte versuche es erneut.');
        }

        return $this->redirectToRoute('admin_restaurant_index');
    }
}
