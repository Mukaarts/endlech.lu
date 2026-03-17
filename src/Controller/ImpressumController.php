<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ImpressumController extends AbstractController
{
    #[Route('/legal', name: 'app_impressum')]
    public function index(): Response
    {
        return $this->render('impressum/index.html.twig');
    }
}
