<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\Categorie\Categorie;
use App\Domain\Categorie\CategorieId;
use App\Domain\Categorie\CategorieRepositoryInterface;
use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteId;
use App\Domain\Compte\CompteRepositoryInterface;
use App\Domain\Id\IdGeneratorInterface;
use App\Domain\Mouvement\Montant;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementId;
use App\Domain\Mouvement\MouvementRepositoryInterface;
use App\Domain\Temps\Date;
use App\Domain\Temps\DateInvalideException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class MouvementController extends AbstractController
{
    public function __construct(
        private readonly MouvementRepositoryInterface $mouvementRepository,
        private readonly CompteRepositoryInterface $compteRepository,
        private readonly CategorieRepositoryInterface $categorieRepository,
        private readonly IdGeneratorInterface $idGenerator,
    ) {
    }

    #[Route('/mouvements/edit', name: 'mouvements_edit', methods: ['POST'])]
    public function __invoke(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('mouvements_edit', $request->request->getString('_token'))) {
            throw new AccessDeniedHttpException('Jeton CSRF invalide.');
        }

        // Valeurs postées
        $action = $request->request->get('action');
        $batchArray = $request->request->all('batch');
        $mouvementsArray = $request->request->all('mouvements');

        /** @var string $mouvementId */
        foreach ($batchArray as $mouvementId) {
            if (isset($mouvementsArray[$mouvementId])) {
                $mouvementArray = $mouvementsArray[$mouvementId];

                $mouvementId = MouvementId::estValide($mouvementId) ?
                    new MouvementId($mouvementId) :
                    null;

                switch ($action) {
                    case 'save': // Création et édition
                        // Date
                        if (isset($mouvementArray['date'])) {
                            $dateString = $mouvementArray['date'];
                            try {
                                $date = Date::créer('d-m-Y H:i:s', "$dateString 00:00:00");
                            } catch (DateInvalideException $e) {
                                throw new BadRequestHttpException(sprintf('Date du mouvement invalide : [%s].', $dateString), $e);
                            }
                        }

                        // Catégorie
                        if (isset($mouvementArray['categorie'])) {
                            $categorieId = CategorieId::estValide((string) $mouvementArray['categorie']) ?
                                new CategorieId((string) $mouvementArray['categorie']) :
                                null;

                            if ($categorieId instanceof CategorieId) {
                                $categorie = $this->categorieRepository->find($categorieId);

                                if (!$categorie instanceof Categorie) {
                                    throw new BadRequestHttpException(sprintf('Catégorie [%s] introuvable.', $categorieId));
                                }
                            } else {
                                $categorie = null;
                            }
                        }

                        // Compte
                        if (isset($mouvementArray['compte'])) {
                            if (CompteId::estValide((string) $mouvementArray['compte'])) {
                                $compteId = new CompteId((string) $mouvementArray['compte']);
                                $compte = $this->compteRepository->find($compteId);

                                if (!$compte instanceof Compte) {
                                    throw new BadRequestHttpException(sprintf('Compte [%s] introuvable.', $compteId));
                                }
                            }
                        }

                        // Montant
                        if (isset($mouvementArray['montant'])) {
                            $montant = new Montant((float) str_replace(',', '.', $mouvementArray['montant']));
                        }

                        // Description
                        if (isset($mouvementArray['description'])) {
                            $description = $mouvementArray['description'];
                        }

                        $variablesDéfinies = get_defined_vars();

                        if ($mouvementId instanceof MouvementId) { // Édition
                            $mouvement = $this->mouvementRepository->find($mouvementId);

                            if (!$mouvement instanceof Mouvement) {
                                throw new BadRequestHttpException(sprintf('Mouvement [%s] introuvable.', $mouvementId));
                            }

                            if (array_key_exists('date', $variablesDéfinies)) {
                                $mouvement->date = $date;
                            }
                            if (array_key_exists('categorie', $variablesDéfinies)) {
                                $mouvement->categorie = $categorie;
                            }
                            if (array_key_exists('compte', $variablesDéfinies)) {
                                $mouvement->compte = $compte;
                            }
                            if (array_key_exists('montant', $variablesDéfinies)) {
                                $mouvement->montant = $montant;
                            }
                            if (array_key_exists('description', $variablesDéfinies)) {
                                $mouvement->description = $description;
                            }
                        } else { // Création
                            if (
                                !array_key_exists('date', $variablesDéfinies)
                                || !array_key_exists('categorie', $variablesDéfinies)
                                || !array_key_exists('compte', $variablesDéfinies)
                                || !array_key_exists('montant', $variablesDéfinies)
                                || !array_key_exists('description', $variablesDéfinies)
                            ) {
                                throw new BadRequestHttpException("Les valeurs nécessaires à la création d'un mouvement ne sont pas toutes postées.");
                            }

                            $mouvement = new Mouvement(
                                new MouvementId((string) $this->idGenerator->générer()),
                                $date,
                                $categorie,
                                $compte,
                                $montant,
                                $description
                            );
                        }

                        // La date d'un mouvement est encadrée par celles de son compte
                        if (!$mouvement->compte->estOuvertLe($mouvement->date)) {
                            $message = sprintf(
                                'La date du mouvement [%s] est en dehors des dates du compte [%s], ouvert %s.',
                                $mouvement->date->format('d-m-Y'),
                                $mouvement->compte,
                                $mouvement->compte->dateFermeture instanceof Date ?
                                    sprintf('du [%s] au [%s]', $mouvement->compte->dateOuverture->format('d-m-Y'), $mouvement->compte->dateFermeture->format('d-m-Y')) :
                                    sprintf('depuis le [%s]', $mouvement->compte->dateOuverture->format('d-m-Y')),
                            );
                            throw new BadRequestHttpException($message);
                        }

                        $this->mouvementRepository->save($mouvement);

                        break;

                    case 'delete': // Suppression
                        if ($mouvementId instanceof MouvementId) {
                            $this->mouvementRepository->delete($mouvementId);
                        }

                        break;
                }
            }
        }

        return $this->redirectToRoute(
            $request->request->getString('redirect_route'),
            $request->request->all('redirect_params')
        );
    }
}
