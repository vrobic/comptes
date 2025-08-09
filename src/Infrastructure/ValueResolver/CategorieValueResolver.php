<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueResolver;

use App\Domain\Categorie\Categorie;
use App\Domain\Categorie\CategorieId;
use App\Domain\Categorie\CategorieRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class CategorieValueResolver implements ValueResolverInterface
{
    public function __construct(
        private CategorieRepositoryInterface $categorieRepository,
    ) {
    }

    /** @return array<?Categorie> */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (Categorie::class !== $argument->getType() || !$request->attributes->has('categorieId')) {
            return [];
        }

        $categorieId = $request->attributes->get('categorieId') ?: null;

        if ('aucune' === $categorieId) {
            $categorieId = null;
        }

        if ($argument->isNullable() && is_null($categorieId)) {
            return [null];
        }

        if (!is_string($categorieId) || !CategorieId::estValide($categorieId)) {
            throw new BadRequestHttpException("La catégorie $categorieId est invalide.");
        }

        $categorieId = new CategorieId($categorieId);

        $categorie = $this->categorieRepository->find($categorieId);

        if (!($categorie instanceof Categorie)) {
            throw new NotFoundHttpException("La catégorie $categorieId n'existe pas.");
        }

        return [$categorie];
    }
}
