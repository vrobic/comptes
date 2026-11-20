<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\BalanceMensuelle;
use App\Domain\Categorie\CategorieRepositoryInterface;
use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteId;
use App\Domain\Compte\CompteRepositoryInterface;
use App\Domain\Compte\Plafond;
use App\Domain\Compte\Solde;
use App\Domain\DataStructure\Maybe;
use App\Domain\Id\IdGeneratorInterface;
use App\Domain\Mouvement\Montant;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementId;
use App\Domain\Mouvement\MouvementRepositoryInterface;
use App\Domain\Temps\Depuis;
use App\Domain\Temps\Mois;
use App\Domain\Temps\Periode;
use App\Infrastructure\ValueResolver\PeriodeParDefautAttribute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CompteController extends AbstractController
{
    public function __construct(
        private readonly CompteRepositoryInterface $compteRepository,
        private readonly MouvementRepositoryInterface $mouvementRepository,
        private readonly CategorieRepositoryInterface $categorieRepository,
        private readonly IdGeneratorInterface $idGenerator,
    ) {
    }

    #[Route('/', name: 'comptes_comptes', methods: ['GET'])]
    public function liste(Request $request): Response
    {
        $comptes = $this->compteRepository->findAll();
        $avecDétails = filter_var($request->query->get('avec_details', false), FILTER_VALIDATE_BOOL);

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

        // Sans aucun mouvement (ni solde initial), la collection est vide
        $firstMouvement = $mouvements->isEmpty() ? null : $mouvements->first();
        $lastMouvement = $mouvements->isEmpty() ? null : $mouvements->last();

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
                'comptes' => $avecDétails ? $comptes : $comptes->ouverts(),
                'avec_details' => $avecDétails,
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

    #[Route('/compte/{compteId}', name: 'comptes_compte', methods: ['GET'])]
    public function détail(
        Request $request,
        Compte $compte,
        #[PeriodeParDefautAttribute(Depuis::UN_MOIS)]
        Periode $période,
    ): Response {
        // Sans filtre de période et si le compte est clôturé, sa dernière année
        if (!$request->query->all('date_filter') && $compte->dateFermeture instanceof \DateTimeImmutable) {
            $période = new Periode(max($compte->dateOuverture, $compte->dateFermeture->modify('-1 year')), $compte->dateFermeture);
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
                'a_des_mouvements' => $this->mouvementRepository->aDesMouvements($compte->id),
            ]
        );
    }

    #[Route('/comptes/new', name: 'comptes_new', methods: ['GET', 'POST'])]
    public function création(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('comptes_edit', $request->request->getString('_token'))) {
                throw new AccessDeniedHttpException('Jeton CSRF invalide.');
            }

            $compte = $this->construireLeCompte($request, null);
            $this->compteRepository->save($compte);

            return $this->redirectToRoute('comptes_compte', ['compteId' => (string) $compte->id]);
        }

        return $this->render(
            'Compte/edit.html.twig',
            [
                'compte' => null,
            ]
        );
    }

    #[Route('/compte/{compteId}/edit', name: 'comptes_edit', methods: ['GET', 'POST'])]
    public function édition(Request $request, Compte $compte): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('comptes_edit', $request->request->getString('_token'))) {
                throw new AccessDeniedHttpException('Jeton CSRF invalide.');
            }

            $compte = $this->construireLeCompte($request, $compte);
            $this->compteRepository->save($compte);

            return $this->redirectToRoute('comptes_compte', ['compteId' => (string) $compte->id]);
        }

        return $this->render(
            'Compte/edit.html.twig',
            [
                'compte' => $compte,
            ]
        );
    }

    #[Route('/compte/{compteId}/delete', name: 'comptes_delete', methods: ['POST'])]
    public function suppression(Request $request, Compte $compte): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('comptes_delete', $request->request->getString('_token'))) {
            throw new AccessDeniedHttpException('Jeton CSRF invalide.');
        }

        // Un compte qui a des mouvements se ferme, mais ne se supprime pas
        if ($this->mouvementRepository->aDesMouvements($compte->id)) {
            throw new BadRequestHttpException(sprintf('Le compte [%s] a des mouvements : il ne peut pas être supprimé.', $compte));
        }

        $this->compteRepository->delete($compte->id);

        return $this->redirectToRoute('comptes_comptes');
    }

    /**
     * Construit, à partir des valeurs postées, un nouveau compte ou le compte édité.
     */
    private function construireLeCompte(Request $request, ?Compte $compte): Compte
    {
        // Nom
        $nom = trim($request->request->getString('nom'));
        if ('' === $nom) {
            throw new BadRequestHttpException('Le nom du compte est obligatoire.');
        }

        // Banque
        $banque = trim($request->request->getString('banque'));
        if ('' === $banque) {
            throw new BadRequestHttpException('La banque du compte est obligatoire.');
        }

        // Numéro
        $numero = trim($request->request->getString('numero'));
        if ('' === $numero) {
            throw new BadRequestHttpException('Le numéro du compte est obligatoire.');
        }

        // Plafond, en euros entiers comme en base
        $plafondString = trim($request->request->getString('plafond'));
        $plafond = null;
        if ('' !== $plafondString) {
            $plafondEntier = filter_var($plafondString, FILTER_VALIDATE_INT);
            if (false === $plafondEntier) {
                throw new BadRequestHttpException(sprintf('Plafond du compte invalide : [%s].', $plafondString));
            }
            $plafond = new Plafond((float) $plafondEntier);
        }

        // Solde initial
        $soldeInitialString = str_replace(',', '.', trim($request->request->getString('solde_initial')));
        if (!is_numeric($soldeInitialString)) {
            throw new BadRequestHttpException(sprintf('Solde initial du compte invalide : [%s].', $soldeInitialString));
        }
        $soldeInitial = new Solde((float) $soldeInitialString);

        // Date d'ouverture
        $dateOuvertureString = trim($request->request->getString('date_ouverture'));
        $dateOuverture = \DateTimeImmutable::createFromFormat('d-m-Y H:i:s', "$dateOuvertureString 00:00:00");
        // createFromFormat décale les dates impossibles (31-02 devient 03-03) et lit 26 comme l'an 26 : la date relue doit être celle saisie
        if (!$dateOuverture instanceof \DateTimeImmutable || $dateOuverture->format('d-m-Y') !== $dateOuvertureString) {
            throw new BadRequestHttpException(sprintf("Date d'ouverture du compte invalide : [%s].", $dateOuvertureString));
        }

        // Date de fermeture
        $dateFermetureString = trim($request->request->getString('date_fermeture'));
        $dateFermeture = null;
        if ('' !== $dateFermetureString) {
            $dateFermeture = \DateTimeImmutable::createFromFormat('d-m-Y H:i:s', "$dateFermetureString 00:00:00");
            // createFromFormat décale les dates impossibles (31-02 devient 03-03) et lit 26 comme l'an 26 : la date relue doit être celle saisie
            if (!$dateFermeture instanceof \DateTimeImmutable || $dateFermeture->format('d-m-Y') !== $dateFermetureString) {
                throw new BadRequestHttpException(sprintf('Date de fermeture du compte invalide : [%s].', $dateFermetureString));
            }
        }

        // Rang
        $rangString = trim($request->request->getString('rang'));
        $rang = null;
        if ('' !== $rangString) {
            $rang = filter_var($rangString, FILTER_VALIDATE_INT);
            if (false === $rang) {
                throw new BadRequestHttpException(sprintf('Rang du compte invalide : [%s].', $rangString));
            }
        }

        // Les règles du domaine (plafond positif, fermeture après l'ouverture) sont des erreurs de saisie
        try {
            $compteSaisi = new Compte(
                $compte->id ?? new CompteId((string) $this->idGenerator->générer()),
                $nom,
                $numero,
                $banque,
                $plafond,
                $soldeInitial,
                $compte->solde ?? $soldeInitial, // calculé en base, le solde n'est pas enregistré
                $dateOuverture,
                $dateFermeture,
                $rang,
            );
        } catch (\DomainException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        // Les dates d'un compte encadrent celles de ses mouvements
        $périodeDesMouvements = $compte instanceof Compte ? $this->mouvementRepository->getPériodeDesMouvements($compte->id) : null;
        if ($périodeDesMouvements instanceof Periode && !$compteSaisi->estOuvertPendant($périodeDesMouvements)) {
            $message = sprintf(
                'Les dates du compte doivent encadrer celles de ses mouvements, du [%s] au [%s].',
                $périodeDesMouvements->début->format('d-m-Y'),
                $périodeDesMouvements->fin->format('d-m-Y'),
            );
            throw new BadRequestHttpException($message);
        }

        return $compteSaisi;
    }
}
