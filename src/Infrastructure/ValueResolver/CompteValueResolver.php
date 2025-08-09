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

    /** @return array<Compte> */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (Compte::class !== $argument->getType() || !$request->attributes->has('compteId')) {
            return [];
        }

        $compteId = $request->attributes->get('compteId');

        if (!is_string($compteId) || !CompteId::estValide($compteId)) {
            throw new BadRequestHttpException("Le compte $compteId est invalide.");
        }

        $compteId = new CompteId($compteId);

        $compte = $this->compteRepository->find($compteId);

        if (!($compte instanceof Compte)) {
            throw new NotFoundHttpException("Le compte $compteId n'existe pas.");
        }

        return [$compte];
    }
}
