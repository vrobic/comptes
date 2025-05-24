<?php

namespace ComptesBundle\Command;

use ComptesBundle\Entity\Categorie;
use ComptesBundle\Entity\Repository\CategorieRepository;
use ComptesBundle\Service\ImportHandler\MouvementsImportHandlerInterface;
use ComptesBundle\Service\MouvementCategorizer;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question;

/**
 * Script d'import de mouvements bancaires depuis un fichier.
 */
class MouvementsImportCommand extends AbstractImportCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('comptes:import:mouvements');
        $this->setDescription("Importe les mouvements d'un compte bancaire.");
        $this->addArgument('filename', InputArgument::REQUIRED, "Fichier depuis lequel importer les mouvements.");
        $this->addArgument('handler', InputArgument::REQUIRED, "Handler à utiliser.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');
        $filename = $input->getArgument('filename');
        $handlerIdentifier = $input->getArgument('handler');

        // Définit le type d'import
        $this->setType('mouvements');

        // Chargement de la configuration
        $this->loadConfiguration();

        /** @var MouvementsImportHandlerInterface $handler */
        $handler = $this->getHandler($handlerIdentifier);
        $splFile = $this->getFile($filename);
        $handler->parse($splFile);

        /** @var RegistryInterface $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        // Indicateurs
        $i = 0; // Nombre de mouvements importés
        $balance = 0; // Balance des mouvements (crédit ou débit)

        // 1. Les mouvements catégorisés
        $categorizedMouvements = $handler->getCategorizedMouvements();

        if ($categorizedMouvements) {
            $output->writeln("<info>Mouvements catégorisés</info>", OutputInterface::VERBOSITY_VERBOSE);

            foreach ($categorizedMouvements as $mouvement) {
                $output->writeln("<comment>{$mouvement}</comment>");

                // Indicateurs
                $i++;
                $balance += $mouvement->getMontant();

                // Enregistrement
                $em->persist($mouvement);
            }
        }

        // 2. Les mouvements non catégorisés
        $uncategorizedMouvements = $handler->getUncategorizedMouvements();

        if ($uncategorizedMouvements) {
            $output->writeln("<info>Mouvements non catégorisés</info>", OutputInterface::VERBOSITY_VERBOSE);

            /** @var CategorieRepository $categorieRepository */
            $categorieRepository = $em->getRepository('ComptesBundle:Categorie');
            $categories = $categorieRepository->findAll();

            foreach ($uncategorizedMouvements as $mouvement) {
                $output->writeln("<comment>{$mouvement}</comment>");

                if ($categories) {
                    $answers = [
                        'n' => "Ne pas catégoriser",
                    ];

                    foreach ($categories as $categorie) {
                        if (!($categorie->getCategorieParente() instanceof Categorie)) {
                            $categorieId = $categorie->getId();
                            $categoriesLine = $this->getCategoriesLine($categorie, [$categorie]);
                            $answers[$categorieId] = implode(" / ", $categoriesLine);

                            foreach ($categorie->getCategoriesFillesRecursive() as $categorieFille) {
                                $categorieFilleId = $categorieFille->getId();
                                $categoriesLine = $this->getCategoriesLine($categorieFille, [$categorieFille]);
                                $answers[$categorieFilleId] = implode(" / ", $categoriesLine);
                            }
                        }
                    }

                    // Question à l'utilisateur
                    $question = new Question\ChoiceQuestion("<question>Catégories disponibles</question>", $answers);
                    $question->setAutocompleterValues([]);
                    $question->setPrompt("<question>Catégorie ? ></question> ");

                    // L'identifiant de la catégorie
                    $categorieId = $questionHelper->ask($input, $output, $question);

                    if (strtolower($categorieId) !== 'n') { // Réponse insensible à la casse
                        /** @var ?Categorie $categorie */
                        $categorie = $categorieRepository->find($categorieId); // @todo : voir que faire du null
                        $mouvement->setCategorie($categorie);
                    }
                }

                // Indicateurs
                $i++;
                $balance += $mouvement->getMontant();

                // Enregistrement
                $em->persist($mouvement);
            }
        }

        // 3. Les mouvements dont la catégorie n'a pas pu être formellement déterminée
        $ambiguousMouvements = $handler->getAmbiguousMouvements();

        if ($ambiguousMouvements) {
            $output->writeln("<info>Mouvements ambigus</info>", OutputInterface::VERBOSITY_VERBOSE);

            /**
             * Service de catégorisation automatique des mouvements.
             *
             * @var MouvementCategorizer $mouvementCategorizer
             */
            $mouvementCategorizer = $this->getContainer()->get('comptes_bundle.mouvement.categorizer');

            foreach ($ambiguousMouvements as $mouvement) {
                $output->writeln("<comment>{$mouvement}</comment>");

                // Catégorisation automatique du mouvement
                $categories = $mouvementCategorizer->getCategories($mouvement);

                if ($categories) {
                    $answers = [
                        'n' => "Ne pas catégoriser",
                    ];

                    foreach ($categories as $key => $categorie) {
                        $answers[$key] = $categorie;
                    }

                    // Question à l'utilisateur
                    $question = new Question\ChoiceQuestion("<question>Proposition de catégories</question>", $answers);
                    $question->setAutocompleterValues([]);
                    $question->setPrompt("<question>Catégorie ? ></question> ");

                    // La clé de la catégorie au sein du tableau $categories
                    $categorieKey = $questionHelper->ask($input, $output, $question);

                    if (strtolower($categorieKey) !== 'n') { // Réponse insensible à la casse
                        $categorie = $categories[$categorieKey];
                        $mouvement->setCategorie($categorie);
                    }
                }

                // Indicateurs
                $i++;
                $balance += $mouvement->getMontant();

                // Enregistrement
                $em->persist($mouvement);
            }
        }

        // 4. Les mouvements suspectés comme doublons, qui nécessitent une confirmation manuelle
        $waitingMouvements = $handler->getWaitingMouvements();

        foreach ($waitingMouvements as $mouvement) {
            $output->writeln("<info>Mouvements à valider</info>", OutputInterface::VERBOSITY_VERBOSE);

            $output->writeln("<comment>{$mouvement}</comment>");

            // Question à l'utilisateur
            $question = new Question\ConfirmationQuestion("<question>Un mouvement similaire existe déjà :\n\t{$mouvement}\nImporter (y/N) ?</question>", false);

            $confirm = $questionHelper->ask($input, $output, $question);

            if ($confirm) {
                // Indicateurs
                $i++;
                $balance += $mouvement->getMontant();

                // Enregistrement
                $em->persist($mouvement);
            }
        }

        // Persistance des données
        $em->flush();

        // Indicateurs
        $mouvements = $handler->getMouvements();
        $mouvementsCount = count($mouvements);
        $output->writeln("<info>{$i} mouvements importés sur {$mouvementsCount} pour une balance de {$balance}€</info>");

        return 0;
    }

    /**
     * Récupère la lignée d'une catégorie, récursivement.
     *
     * @param Categorie[] $array
     *
     * @return Categorie[]
     */
    private function getCategoriesLine(?Categorie $categorie, array $array): array
    {
        if ($categorie instanceof Categorie) {
            $categorieParente = $categorie->getCategorieParente();
            if ($categorieParente instanceof Categorie) {
                array_unshift($array, $categorieParente);
                $array = $this->getCategoriesLine($categorieParente, $array);
            }
        }

        return $array;
    }
}
