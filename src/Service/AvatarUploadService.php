<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AvatarUploadService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/avatars')]
        private string $uploadDir,
        private EntityManagerInterface $em,
    ) {
    }

    public function upload(UploadedFile $file, User $user): string
    {
        $oldFilename = $user->getAvatarFilename();
        if ($oldFilename) {
            $oldPath = $this->uploadDir . '/' . $oldFilename;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $filename = uniqid('', true) . '.' . $file->guessExtension();
        $file->move($this->uploadDir, $filename);

        $user->setAvatarFilename($filename);
        $this->em->flush();

        return $filename;
    }

    public function delete(User $user): void
    {
        $filename = $user->getAvatarFilename();
        if ($filename) {
            $path = $this->uploadDir . '/' . $filename;
            if (file_exists($path)) {
                unlink($path);
            }
            $user->setAvatarFilename(null);
            $this->em->flush();
        }
    }
}
