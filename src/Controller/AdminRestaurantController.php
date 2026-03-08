<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Form\RestaurantType;
use App\Repository\RestaurantImageRepository;
use App\Repository\RestaurantRepository;
use App\Repository\RestaurantSuggestionRepository;
use App\Service\ImageUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
            'verifiedCount' => $restaurantRepository->countVerified(),
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
        $wasVerified = $restaurant->isVerified();
        $form = $this->createForm(RestaurantType::class, $restaurant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNowVerified = $restaurant->isVerified();
            if ($isNowVerified && !$wasVerified) {
                $restaurant->setVerifiedAt(new \DateTimeImmutable());
                $restaurant->setVerifiedBy($this->getUser());
            } elseif (!$isNowVerified && $wasVerified) {
                $restaurant->setVerifiedAt(null);
                $restaurant->setVerifiedBy(null);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Restaurant erfolgreich aktualisiert.');

            return $this->redirectToRoute('admin_restaurant_index');
        }

        return $this->render('admin/restaurant/edit.html.twig', [
            'restaurant' => $restaurant,
            'form' => $form,
        ]);
    }

    #[Route('/restaurants/{id}/verifizieren', name: 'admin_restaurant_toggle_verified', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleVerified(Restaurant $restaurant, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('toggle-verified-' . $restaurant->getId(), $request->request->getString('_token'))) {
            if ($restaurant->isVerified()) {
                $restaurant->setIsVerified(false);
                $restaurant->setVerifiedAt(null);
                $restaurant->setVerifiedBy(null);
                $this->addFlash('success', 'Verifizierung für „' . $restaurant->getName() . '" aufgehoben.');
            } else {
                $restaurant->setIsVerified(true);
                $restaurant->setVerifiedAt(new \DateTimeImmutable());
                $restaurant->setVerifiedBy($this->getUser());
                $this->addFlash('success', '„' . $restaurant->getName() . '" als verifiziert markiert.');
            }
            $em->flush();
        } else {
            $this->addFlash('error', 'Ungültiges CSRF-Token. Bitte versuche es erneut.');
        }

        return $this->redirectToRoute('admin_restaurant_index');
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

    #[Route('/restaurants/{id}/fotos', name: 'admin_restaurant_image_upload', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function uploadImage(Restaurant $restaurant, Request $request, ImageUploadService $imageUploadService): Response
    {
        if (!$this->isCsrfTokenValid('upload-images-' . $restaurant->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Ungültiges CSRF-Token. Bitte versuche es erneut.');

            return $this->redirectToRoute('admin_restaurant_edit', ['id' => $restaurant->getId()]);
        }

        $files = $request->files->get('images', []);
        $altText = $request->request->getString('altText', '');
        $uploaded = 0;

        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $imageUploadService->upload($file, $restaurant, $altText);
                ++$uploaded;
            }
        }

        if ($uploaded > 0) {
            $this->addFlash('success', $uploaded.' Foto(s) erfolgreich hochgeladen.');
        } else {
            $this->addFlash('error', 'Keine gültigen Dateien gefunden.');
        }

        return $this->redirectToRoute('admin_restaurant_edit', ['id' => $restaurant->getId()]);
    }

    #[Route('/restaurants/{id}/fotos/{imageId}/loeschen', name: 'admin_restaurant_image_delete', requirements: ['id' => '\d+', 'imageId' => '\d+'], methods: ['POST'])]
    public function deleteImage(Restaurant $restaurant, int $imageId, Request $request, RestaurantImageRepository $imageRepository, ImageUploadService $imageUploadService): Response
    {
        if (!$this->isCsrfTokenValid('delete-image-' . $imageId, $request->request->getString('_token'))) {
            $this->addFlash('error', 'Ungültiges CSRF-Token. Bitte versuche es erneut.');

            return $this->redirectToRoute('admin_restaurant_edit', ['id' => $restaurant->getId()]);
        }

        $image = $imageRepository->find($imageId);
        if ($image && $image->getRestaurant() === $restaurant) {
            $imageUploadService->delete($image);
            $this->addFlash('success', 'Foto erfolgreich gelöscht.');
        } else {
            $this->addFlash('error', 'Foto nicht gefunden.');
        }

        return $this->redirectToRoute('admin_restaurant_edit', ['id' => $restaurant->getId()]);
    }
}
