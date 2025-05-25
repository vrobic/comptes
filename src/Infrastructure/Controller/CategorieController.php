<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Infrastructure\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategorieController extends AbstractController
{
    public function __construct(
        private readonly CategorieRepository $categorieRepository,
    ) {
    }

    #[Route('/categories', name: 'categories_categories')]
    public function liste(): Response
    {
        $categories = $this->categorieRepository->findAll();

        return $this->render(
            'Categorie/index.html.twig',
            [
                'categories' => $categories,
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
