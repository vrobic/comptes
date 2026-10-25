<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueResolver;

use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteId;
use App\Domain\Compte\CompteRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class CompteValueResolver implements ValueResolverInterface
{
    public function __construct(
        private CompteRepositoryInterface $compteRepository,
    ) {
    }

    /** @return array<?Compte> */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (Compte::class !== $argument->getType()) {
            return [];
        }

        // compteId a deux sources légitimes : la route sur /compte/{compteId}, et à défaut la query string
        // sur /categories et /categorie/{categorieId}, où le <select name="compteId"> des formulaires GET et les liens l'ajoutent
        // Le front peut envoyer une string vide, d'où la ternaire
        $compteId = ($request->attributes->get('compteId') ?? $request->query->get('compteId')) ?: null;

        if ($argument->isNullable() && is_null($compteId)) {
            return [null];
        }

        if (!is_string($compteId) || !CompteId::estValide($compteId)) {
            throw new BadRequestHttpException(sprintf('Le compte [%s] est invalide.', $compteId));
        }

        $compteId = new CompteId($compteId);

        $compte = $this->compteRepository->find($compteId);

        if (!$compte instanceof Compte) {
            throw new NotFoundHttpException(sprintf("Le compte [%s] n'existe pas.", $compteId));
        }

        return [$compte];
    }
}
