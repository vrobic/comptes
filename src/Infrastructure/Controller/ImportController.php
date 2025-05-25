<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Infrastructure\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ImportController extends AbstractController
{
    public function __construct(
        private readonly CategorieRepository $categorieRepository,
    ) {
    }

    #[Route('/import/mouvements', name: 'import_mouvements')]
    public function liste(): Response
    {
        return $this->render(
            'Import/mouvements.html.twig',
            [
            ]
        );
    }

    #[Route('/categorie/{categorieId}', name: 'categories_categorie')]
    public function détail(int $categorieId): Response
    {
        $categorie = $this->categorieRepository->find($categorieId);

        return $this->render(
            'Categorie/show.html.twig',
            [
                'categorie' => $categorie,
            ]
        );
    }
}
