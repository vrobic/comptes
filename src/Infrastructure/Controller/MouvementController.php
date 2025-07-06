<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\Categorie\Categorie;
use App\Domain\Categorie\CategorieId;
use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteId;
use App\Domain\Id\IdGeneratorInterface;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementId;
use App\Infrastructure\Repository\CategorieRepository;
use App\Infrastructure\Repository\CompteRepository;
use App\Infrastructure\Repository\MouvementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class MouvementController extends AbstractController
{
    public function __construct(
        private readonly MouvementRepository $mouvementRepository,
        private readonly CompteRepository $compteRepository,
        private readonly CategorieRepository $categorieRepository,
        private readonly IdGeneratorInterface $idGenerator,
    ) {
    }

    #[Route('/mouvements/edit', name: 'mouvements_edit')]
    public function __invoke(Request $request): RedirectResponse
    {
        // Valeurs postées
        $action = $request->get('action');
        $batchArray = $request->get('batch', []);
        $mouvementsArray = $request->get('mouvements', []);

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
                            $date = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateString 00:00:00");

                            if (!($date instanceof \DateTime)) {
                                throw new BadRequestHttpException("Date du mouvement invalide : $dateString");
                            }
                        }

                        // Catégorie
                        if (isset($mouvementArray['categorie'])) {
                            $categorieId = CategorieId::estValide((string) $mouvementArray['categorie']) ?
                                new CategorieId((string) $mouvementArray['categorie']) :
                                null;

                            if ($categorieId instanceof CategorieId) {
                                $categorie = $this->categorieRepository->find($categorieId);

                                if (!($categorie instanceof Categorie)) {
                                    throw new BadRequestHttpException("Catégorie $categorieId introuvable");
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

                                if (!($compte instanceof Compte)) {
                                    throw new BadRequestHttpException("Compte $compteId introuvable");
                                }
                            }
                        }

                        // Montant
                        if (isset($mouvementArray['montant'])) {
                            $montant = (float) str_replace(',', '.', $mouvementArray['montant']);
                        }

                        // Description
                        if (isset($mouvementArray['description'])) {
                            $description = $mouvementArray['description'];
                        }

                        $variablesDéfinies = get_defined_vars();

                        if ($mouvementId instanceof MouvementId) { // Édition
                            $mouvement = $this->mouvementRepository->find($mouvementId);

                            if (!($mouvement instanceof Mouvement)) {
                                throw new BadRequestHttpException("Mouvement $mouvementId introuvable");
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

        // URL de redirection
        $redirectURL = $request->get('redirect_url');

        return $this->redirect($redirectURL);
    }
}
