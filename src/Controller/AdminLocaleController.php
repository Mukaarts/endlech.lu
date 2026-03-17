<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminLocaleController extends AbstractController
{
    private const ALLOWED_LOCALES = ['lb', 'de', 'fr', 'en'];

    #[Route('/locale/{locale}', name: 'admin_set_locale', requirements: ['locale' => 'lb|de|fr|en'])]
    public function setLocale(string $locale, Request $request): RedirectResponse
    {
        if (in_array($locale, self::ALLOWED_LOCALES, true)) {
            $request->getSession()->set('_locale', $locale);
        }

        $referer = $request->headers->get('referer');

        return $this->redirect($referer ?: $this->generateUrl('admin_dashboard'));
    }
}
