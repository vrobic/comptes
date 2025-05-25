<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\Categorie\Categorie;
use App\Domain\Compte\Compte;
use App\Domain\Mouvement\Mouvement;
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
    ) {
    }

    #[Route('/mouvements/edit', name: 'mouvements_edit')]
    public function __invoke(Request $request): RedirectResponse
    {
        // Valeurs postées
        $action = $request->get('action');
        $batchArray = $request->get('batch', []);
        $mouvementsArray = $request->get('mouvements', []);

        foreach ($batchArray as $mouvementID) {
            $mouvementID = (int) $mouvementID;

            if (isset($mouvementsArray[$mouvementID])) {
                $mouvementArray = $mouvementsArray[$mouvementID];

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
                            $categorieID = (int) $mouvementArray['categorie'];

                            if ($categorieID > 0) {
                                $categorie = $this->categorieRepository->find($categorieID);

                                if (!($categorie instanceof Categorie)) {
                                    throw new BadRequestHttpException("Catégorie $categorieID introuvable");
                                }
                            } else {
                                $categorie = null;
                            }
                        }

                        // Compte
                        if (isset($mouvementArray['compte'])) {
                            $compteID = (int) $mouvementArray['compte'];

                            if ($compteID > 0) {
                                $compte = $this->compteRepository->find($compteID);

                                if (!($compte instanceof Compte)) {
                                    throw new BadRequestHttpException("Compte $compteID introuvable");
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

                        if ($mouvementID > 0) { // Édition
                            $mouvement = $this->mouvementRepository->find($mouvementID);

                            if (!($mouvement instanceof Mouvement)) {
                                throw new BadRequestHttpException("Mouvement $mouvementID introuvable");
                            }

                            if (array_key_exists('date', $variablesDéfinies)) {
                                $mouvement->setDate($date);
                            }
                            if (array_key_exists('categorie', $variablesDéfinies)) {
                                $mouvement->setCategorie($categorie);
                            }
                            if (array_key_exists('compte', $variablesDéfinies)) {
                                $mouvement->setCompte($compte);
                            }
                            if (array_key_exists('montant', $variablesDéfinies)) {
                                $mouvement->setMontant($montant);
                            }
                            if (array_key_exists('description', $variablesDéfinies)) {
                                $mouvement->setDescription($description);
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
                                null,
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
                        if ($mouvementID > 0) {
                            $this->mouvementRepository->delete($mouvementID);
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
