<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\Categorie\CategorieRepositoryInterface;
use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteRepositoryInterface;
use App\Domain\DataStructure\Maybe;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementRepositoryInterface;
use App\Domain\Temps\Mois;
use App\Domain\Temps\Periode;
use App\Domain\Temps\Depuis;
use App\Infrastructure\ValueResolver\PeriodeParDefautAttribute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        // Balance
        $balance = $mouvements->balance();

        // Balance des derniers mois
        $balanceDesDerniersMois = [];
        foreach (range(1, 4) as $nombreMois) {
            $mois = Mois::fromDate(new \DateTimeImmutable("$nombreMois months ago"));

            $balanceDesDerniersMois[(string) $mois] = $mouvements
                ->filtrerParPériode(new Periode($mois->début(), $mois->fin()))
                ->balance();
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
                'mouvements' => $mouvements->getIterator(), // pour pouvoir faire mouvements[key+1] en Twig
                'first_mouvement' => $firstMouvement,
                'last_mouvement' => $lastMouvement,
                'balance' => $balance,
                'balance_des_derniers_mois' => $balanceDesDerniersMois,
                'balance_moyenne_des_derniers_mois' => $balanceMoyenneDesDerniersMois,
                'balance_moyenne_des_mois_positifs' => $balanceMoyenneDesMoisPositifs,
            ]
        );
    }

    #[Route('/compte/{compteId}', name: 'comptes_compte')]
    public function détail(
        Request $request,
        Compte $compte,
        #[PeriodeParDefautAttribute(Depuis::UN_MOIS)]
        Periode $période,
    ): Response {
        // Sans filtre de période et si le compte est clôturé, du début à la fin de sa vie
        if (!$request->get('date_filter') && $compte->dateFermeture instanceof \DateTimeImmutable) {
            $période = new Periode($compte->dateOuverture, $compte->dateFermeture);
        }

        // Tous les mouvements de la période
        $mouvements = $this->mouvementRepository->findBy(
            categoriesIds: Maybe::nothing(),
            compteId: Maybe::from($compte->id),
            dateStart: Maybe::from($période->début),
            dateEnd: Maybe::from($période->fin),
            montant: Maybe::nothing(),
        );

        // Toutes les catégories de mouvements
        $categories = $this->categorieRepository->findAll();

        // Solde du compte en début de période
        if (!$mouvements->isEmpty()) {
            /** @var Mouvement $firstMouvement */
            $firstMouvement = $mouvements->first();
            $firstMouvementDate = $firstMouvement->date;
            $soldeStart = $this->compteRepository->getSoldeÀDate($compte->id, $firstMouvementDate);
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
                    'start' => $période->début,
                    'end' => $période->fin,
                ],
                'mouvements' => $mouvements->getIterator(), // pour pouvoir faire mouvements[key+1] en Twig
                'categories' => $categories->toAssociativeArray(),
                'solde_start' => $soldeStart,
                'balance' => $balance,
            ]
        );
    }
}
