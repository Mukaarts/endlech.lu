<?php

namespace App\Service;

use App\Entity\Restaurant;
use App\Entity\RestaurantImage;
use App\Repository\RestaurantImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageUploadService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/restaurants')]
        private string $uploadDir,
        private EntityManagerInterface $em,
        private RestaurantImageRepository $imageRepo,
    ) {
    }

    public function upload(UploadedFile $file, Restaurant $restaurant, string $altText = ''): RestaurantImage
    {
        $filename = uniqid('', true).'.'.$file->guessExtension();
        $file->move($this->uploadDir, $filename);

        $image = new RestaurantImage();
        $image->setFilename($filename);
        $image->setAltText($altText ?: $restaurant->getName());
        $image->setRestaurant($restaurant);
        $image->setUploadedAt(new \DateTimeImmutable());
        $image->setSortOrder($this->imageRepo->getNextSortOrder($restaurant));

        $this->em->persist($image);
        $this->em->flush();

        return $image;
    }

    public function delete(RestaurantImage $image): void
    {
        $restaurant = $image->getRestaurant();
        $path = $this->uploadDir.'/'.$image->getFilename();
        if (file_exists($path)) {
            unlink($path);
        }
        $this->em->remove($image);
        $this->em->flush();

        $this->reorderAfterDelete($restaurant);
    }

    public function reorderAfterDelete(Restaurant $restaurant): void
    {
        $images = $this->imageRepo->findBy(
            ['restaurant' => $restaurant],
            ['sortOrder' => 'ASC'],
        );
        foreach ($images as $index => $image) {
            $image->setSortOrder($index);
        }
        $this->em->flush();
    }
}
