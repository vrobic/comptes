<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\BalanceMensuelle;
use App\Domain\Categorie\CategorieRepositoryInterface;
use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteRepositoryInterface;
use App\Domain\Compte\Solde;
use App\Domain\DataStructure\Maybe;
use App\Domain\Mouvement\Montant;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementId;
use App\Domain\Mouvement\MouvementRepositoryInterface;
use App\Domain\Temps\Depuis;
use App\Domain\Temps\Mois;
use App\Domain\Temps\Periode;
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

        $mouvements = $this->mouvementRepository->findAll();

        $comptesAvecSoldeInitial = $comptes->filter(
            static fn (Compte $compte): bool => !$compte->soldeInitial->estNul()
        );

        // Intercale les soldes initiaux de chaque compte
        if (!$comptesAvecSoldeInitial->isEmpty()) {
            /** @var Compte $compteAvecSoldeInitial */
            foreach ($comptesAvecSoldeInitial as $compteAvecSoldeInitial) {
                $mouvement = new Mouvement(
                    new MouvementId((string) $compteAvecSoldeInitial->id),
                    $compteAvecSoldeInitial->dateOuverture,
                    null,
                    $compteAvecSoldeInitial,
                    new Montant($compteAvecSoldeInitial->soldeInitial->montant),
                    sprintf("Solde initial à l'ouverture du compte « %s »", $compteAvecSoldeInitial->nom)
                );
                $mouvements = $mouvements->add($mouvement);
            }

            $mouvements = $mouvements->trierParDate();
        }

        $firstMouvement = $mouvements->first();
        $lastMouvement = $mouvements->last();

        // Balance
        $balance = $mouvements->balance();

        // Balance des derniers mois
        $balanceDesDerniersMois = new BalanceMensuelle();
        foreach (range(1, 4) as $nombreMois) {
            $mois = Mois::fromDate(new \DateTimeImmutable("first day of $nombreMois months ago"));

            $balanceDesDerniersMois = $balanceDesDerniersMois->add(
                $mois,
                $mouvements
                    ->filtrerParPériode(new Periode($mois->début(), $mois->fin()))
                    ->balance()
            );
        }

        return $this->render(
            'Compte/index.html.twig',
            [
                'comptes' => !$avecFermés ? $comptes->ouverts() : $comptes,
                'a_des_comptes_fermes' => $aDesComptesFermés,
                'avec_fermes' => $avecFermés,
                'mouvements' => $mouvements->toArray(), // pour pouvoir faire mouvements[key+1] en Twig
                'first_mouvement' => $firstMouvement,
                'last_mouvement' => $lastMouvement,
                'solde' => $comptes->solde(),
                'balance' => $balance,
                'balance_des_derniers_mois' => $balanceDesDerniersMois,
                'balance_moyenne_des_derniers_mois' => $balanceDesDerniersMois->moyenne(),
                'balance_moyenne_des_mois_positifs' => $balanceDesDerniersMois->moyenneDesMoisPositifs(),
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
            maybeCategoriesIds: Maybe::nothing(),
            maybeCompteId: Maybe::from($compte->id),
            maybeDateStart: Maybe::from($période->début),
            maybeDateEnd: Maybe::from($période->fin),
            maybeMontant: Maybe::nothing(),
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
            $soldeStart = Solde::nul();
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
                'mouvements' => $mouvements->toArray(), // pour pouvoir faire mouvements[key+1] en Twig
                'categories' => $categories->toAssociativeArray(),
                'solde_start' => $soldeStart,
                'balance' => $balance,
            ]
        );
    }
}
