<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\Categorie\CategorieRepositoryInterface;
use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteId;
use App\Domain\Compte\CompteRepositoryInterface;
use App\Domain\DataStructure\Maybe;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementRepositoryInterface;
use App\Domain\Temps\Mois;
use App\Domain\Temps\Periode;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CompteController extends AbstractController
{
    public function __construct(
        private readonly CompteRepositoryInterface $compteRepository,
        private readonly MouvementRepositoryInterface $mouvementRepository,
        private readonly CategorieRepositoryInterface $categorieRepository,
    ) {
    }

    #[Route('/', name: 'comptes_comptes')]
    public function liste(Request $request): Response
    {
        $comptes = $this->compteRepository->findAll();
        $aDesComptesFermés = !$comptes->fermés()->isEmpty();
        $avecFermés = filter_var($request->query->get('avec_fermes', false), FILTER_VALIDATE_BOOL);

        if (!$avecFermés) {
            $comptes = $comptes->ouverts();
        }

        $mouvements = $this->mouvementRepository->findAll();
        $firstMouvement = $mouvements->first();
        $lastMouvement = $mouvements->last();

        $balanceDesDerniersMois = [];
        foreach (range(1, 4) as $nombreMois) {
            $mois = Mois::fromDate(new \DateTimeImmutable("$nombreMois months ago"));

            $balanceDesDerniersMois[(string) $mois] = $mouvements->balance(new Periode($mois->début(), $mois->fin()));
        }

        $balanceMoyenneDesDerniersMois = array_sum($balanceDesDerniersMois) / count($balanceDesDerniersMois);
        $balanceDesMoisPositifs = array_filter(
            $balanceDesDerniersMois,
            static fn (float $balance): bool => $balance > 0
        );
        $balanceMoyenneDesMoisPositifs = count($balanceDesMoisPositifs) > 0 ?
            array_sum($balanceDesMoisPositifs) / count($balanceDesMoisPositifs) :
            null;

        return $this->render(
            'Compte/index.html.twig',
            [
                'comptes' => $comptes,
                'a_des_comptes_fermes' => $aDesComptesFermés,
                'avec_fermes' => $avecFermés,
                'mouvements' => $mouvements,
                'first_mouvement' => $firstMouvement,
                'last_mouvement' => $lastMouvement,
                'balance_des_derniers_mois' => $balanceDesDerniersMois,
                'balance_moyenne_des_derniers_mois' => $balanceMoyenneDesDerniersMois,
                'balance_moyenne_des_mois_positifs' => $balanceMoyenneDesMoisPositifs,
            ]
        );
    }

    #[Route('/compte/{compteId}', name: 'comptes_compte')]
    public function détail(
        Request $request,
        string $compteId, // @todo : utiliser un param converter
    ): Response {
        $compteId = CompteId::estValide($compteId) ?
            new CompteId($compteId) :
            null;

        if (!($compteId instanceof CompteId)) {
            throw new BadRequestHttpException();
        }

        $compte = $this->compteRepository->find($compteId);

        if (!($compte instanceof Compte)) {
            throw new NotFoundHttpException();
        }

        // Filtre sur la période
        if ($request->get('date_filter')) {
            $dateFilterString = $request->get('date_filter');
            $dateStartString = $dateFilterString['start'];
            $dateEndString = $dateFilterString['end'];

            $dateStart = \DateTimeImmutable::createFromFormat('d-m-Y H:i:s', "$dateStartString 00:00:00");
            $dateEnd = \DateTimeImmutable::createFromFormat('d-m-Y H:i:s', "$dateEndString 23:59:59");
        } elseif ($compte->dateFermeture instanceof \DateTimeImmutable) { // Si le compte est clôturé, du début à la fin de sa vie
            $dateStart = $compte->dateOuverture;
            $dateEnd = $compte->dateFermeture;
        } else { // Sinon, le mois courant en entier
            list($year, $month, $lastDayOfMonth) = explode('-', date('Y-n-t'));

            $month = (int) $month;
            $year = (int) $year;
            $lastDayOfMonth = (int) $lastDayOfMonth;

            $dateStart = \DateTimeImmutable::createFromFormat('Y-n-j H:i:s', "$year-$month-1 00:00:00");
            $dateEnd = \DateTimeImmutable::createFromFormat('Y-n-j H:i:s', "$year-$month-$lastDayOfMonth 23:59:59");
        }

        if (!($dateStart instanceof \DateTimeImmutable) || !($dateEnd instanceof \DateTimeImmutable) || $dateStart > $dateEnd) {
            throw new BadRequestHttpException('La période de dates est invalide.');
        }

        // Tous les mouvements de la période
        $mouvements = $this->mouvementRepository->findBy(
            categoriesIds: Maybe::nothing(),
            compteId: Maybe::from($compteId),
            dateStart: Maybe::from($dateStart),
            dateEnd: Maybe::from($dateEnd),
            montant: Maybe::nothing(),
        );

        // Toutes les catégories de mouvements
        $categories = $this->categorieRepository->findAll();

        // Solde du compte en début de période
        if (!$mouvements->isEmpty()) {
            /** @var Mouvement $firstMouvement */
            $firstMouvement = $mouvements->first();
            $firstMouvementDate = $firstMouvement->date;
            $soldeStart = $this->compteRepository->getSoldeÀDate($compteId, $firstMouvementDate);
        } else {
            $soldeStart = 0.;
        }

        // Balance des mouvements
        $balance = $mouvements->balance(null);

        return $this->render(
            'Compte/show.html.twig',
            [
                'compte' => $compte,
                'date_filter' => [
                    'start' => $dateStart,
                    'end' => $dateEnd,
                ],
                'mouvements' => $mouvements,
                'categories' => $categories->toAssociativeArray(),
                'solde_start' => $soldeStart,
                'balance' => $balance,
            ]
        );
    }
}
