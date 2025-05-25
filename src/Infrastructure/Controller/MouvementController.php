<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

final class MouvementController extends AbstractController
{
    #[Route('/mouvements/edit', name: 'mouvements_edit')]
    public function __invoke(): RedirectResponse
    {
        // @todo

        return $this->redirectToRoute('dashboard');
    }
}
