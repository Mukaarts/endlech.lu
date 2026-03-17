<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class KriterienController extends AbstractController
{
    #[Route('/criteria', name: 'app_kriterien')]
    public function index(): Response
    {
        return $this->render('kriterien/index.html.twig');
    }
}
