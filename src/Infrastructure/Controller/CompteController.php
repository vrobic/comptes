<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\Mouvement\Mouvement;
use App\Infrastructure\Repository\CategorieRepository;
use App\Infrastructure\Repository\CompteRepository;
use App\Infrastructure\Repository\MouvementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CompteController extends AbstractController
{
    public function __construct(
        private readonly CompteRepository $compteRepository,
        private readonly MouvementRepository $mouvementRepository,
        private readonly CategorieRepository $categorieRepository,
    ) {
    }

    #[Route('/comptes', name: 'comptes_comptes')]
    public function liste(): Response
    {
        $comptes = $this->compteRepository->findAll();
        $mouvements = $this->mouvementRepository->findAll();
        $firstMouvement = $mouvements->first();
        $lastMouvement = $mouvements->last();

        return $this->render(
            'Compte/index.html.twig',
            [
                'comptes' => $comptes,
                'mouvements' => $mouvements,
                'first_mouvement' => $firstMouvement,
                'last_mouvement' => $lastMouvement,
            ]
        );
    }

    #[Route('/compte/{compteId}', name: 'comptes_compte')]
    public function détail(
        Request $request,
        int $compteId,
    ): Response {
        $compte = $this->compteRepository->find($compteId);

        // Filtre sur la période
        if ($request->get('date_filter')) {
            $dateFilterString = $request->get('date_filter');
            $dateStartString = $dateFilterString['start'];
            $dateEndString = $dateFilterString['end'];

            $dateStart = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateStartString 00:00:00");
            $dateEnd = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateEndString 23:59:59");
        } elseif ($compte->getDateFermeture() instanceof \DateTime) { // Si le compte est clôturé, du début à la fin de sa vie
            $dateStart = $compte->getDateOuverture();
            $dateEnd = $compte->getDateFermeture();
        } else { // Sinon, le mois courant en entier
            list($year, $month, $lastDayOfMonth) = explode('-', date('Y-n-t'));

            $month = (int) $month;
            $year = (int) $year;
            $lastDayOfMonth = (int) $lastDayOfMonth;

            $dateStart = \DateTime::createFromFormat('Y-n-j H:i:s', "$year-$month-1 00:00:00");
            $dateEnd = \DateTime::createFromFormat('Y-n-j H:i:s', "$year-$month-$lastDayOfMonth 23:59:59");
        }

        if (!($dateStart instanceof \DateTime) || !($dateEnd instanceof \DateTime) || $dateStart > $dateEnd) {
            throw new BadRequestHttpException('La période de dates est invalide.');
        }

        // Tous les mouvements de la période
        $mouvements = $this->mouvementRepository->findByCompteAndDate($compteId, $dateStart, $dateEnd);

        // Toutes les catégories de mouvements
        $categories = $this->categorieRepository->findAll();

        // Solde du compte en début de période
        if (!$mouvements->isEmpty()) {
            /** @var Mouvement $firstMouvement */
            $firstMouvement = $mouvements->first();
            $firstMouvementDate = $firstMouvement->getDate();
            $soldeStart = $this->compteRepository->getSoldeÀDate($compteId, $firstMouvementDate);
        } else {
            $soldeStart = 0.;
        }

        // Balance des mouvements
        $balance = $mouvements->balance();

        return $this->render(
            'Compte/show.html.twig',
            [
                'compte' => $compte,
                'date_filter' => [
                    'start' => $dateStart,
                    'end' => $dateEnd,
                ],
                'mouvements' => $mouvements,
                'categories' => $categories,
                'solde_start' => $soldeStart,
                'balance' => $balance,
            ]
        );
    }
}
