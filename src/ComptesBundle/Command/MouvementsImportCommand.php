<?php

namespace ComptesBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Script d'import de mouvements bancaires depuis un fichier.
 */
class MouvementsImportCommand extends AbstractImportCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
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
        $dialog = $this->getHelperSet()->get('dialog');
        $interaction = !$input->getOption('no-interaction');
        $filename = $input->getArgument('filename');
        $handlerIdentifier = $input->getArgument('handler');

        // Définit le type d'import
        $this->setType('mouvements');

        // Chargement de la configuration
        $this->loadConfiguration();

        // Parsing du fichier
        $handler = $this->getHandler($handlerIdentifier);
        $splFile = $this->getFile($filename);
        $handler->parse($splFile);

        // Le manager d'entités qui va nous servir à persister les mouvements
        $em = $this->getContainer()->get('doctrine')->getManager();

        // Indicateurs
        $i = 0; // Nombre de mouvements importés
        $balance = 0; // Balance des mouvements (crédit ou débit)

        // 1. Les mouvements catégorisés
        $categorizedMouvements = $handler->getCategorizedMouvements();

        if ($categorizedMouvements) {

            $output->writeln("<info>Mouvements catégorisés</info>", OutputInterface::VERBOSITY_VERBOSE);

            foreach ($categorizedMouvements as $mouvement) {

                $output->writeln("<comment>$mouvement</comment>");

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

            $categorieRepository = $em->getRepository('ComptesBundle:Categorie');
            $categories = $categorieRepository->findAll();

            foreach ($uncategorizedMouvements as $mouvement) {

                $output->writeln("<comment>$mouvement</comment>");

                if ($interaction && $categories) {

                    $question = "<question>Catégories disponibles :\n";

                    foreach ($categories as $key => $categorie) {
                        if ($categorie->getCategorieParente() === null) {

                            $categorieId = $categorie->getId();
                            $categoriesLine = $this->getCategoriesLine($categorie, array($categorie));
                            $question .= "\t($categorieId) : " . implode(" / ", $categoriesLine) . "\n";

                            foreach ($categorie->getCategoriesFillesRecursive() as $categorieFille) {
                                $categorieFilleId = $categorieFille->getId();
                                $categoriesLine = $this->getCategoriesLine($categorieFille, array($categorieFille));
                                $question .= "\t($categorieFilleId) : " . implode(" / ", $categoriesLine) . "\n";
                            }
                        }
                    }

                    $question .= "\t(n) : Ne pas catégoriser\n";

                    $question .= "Quel est votre choix (0, 1, ..., n) ?</question>";

                    // L'identifiant de la catégorie
                    $categorieId = null; // Réponse obligatoire

                    while (strtolower($categorieId) !== "n" && ($categorieId === null || $categorieRepository->find($categorieId) === null)) {
                        $categorieId = $dialog->ask($output, $question);
                    }

                    if (strtolower($categorieId) !== "n") { // Réponse insensible à la casse
                        $categorie = $categorieRepository->find($categorieId);
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

            // Service de catégorisation automatique des mouvements
            $mouvementCategorizer = $this->getContainer()->get('comptes_bundle.mouvement.categorizer');

            foreach ($ambiguousMouvements as $mouvement) {

                $output->writeln("<comment>$mouvement</comment>");

                if ($interaction) {

                    // Catégorisation automatique du mouvement
                    $categories = $mouvementCategorizer->getCategories($mouvement);

                    if ($categories) {

                        $question = "<question>Proposition de catégories :\n";

                        foreach ($categories as $key => $categorie) {
                            $question .= "\t($key) : $categorie\n";
                        }

                        $question .= "\t(n) : Ne pas catégoriser\n";

                        $question .= "Quel est votre choix (0, 1, ..., n) ?</question>";

                        // La clé de la catégorie au sein du tableau $categories
                        $categorieKey = null; // Réponse obligatoire

                        while (strtolower($categorieKey) !== "n" && !isset($categories[$categorieKey])) {
                            $categorieKey = $dialog->ask($output, $question);
                        }

                        if (strtolower($categorieKey) !== "n") { // Réponse insensible à la casse
                            $categorie = $categories[$categorieKey];
                            $mouvement->setCategorie($categorie);
                        }
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
        if ($interaction) {

            $waitingMouvements = $handler->getWaitingMouvements();

            foreach ($waitingMouvements as $mouvement) {

                $output->writeln("<info>Mouvements à valider</info>", OutputInterface::VERBOSITY_VERBOSE);

                $output->writeln("<comment>$mouvement</comment>");

                $confirm = $dialog->askConfirmation($output, "<question>Un mouvement similaire existe déjà :\n\t$mouvement\nImporter (y/N) ?</question>", false);

                if ($confirm) {

                    // Indicateurs
                    $i++;
                    $balance += $mouvement->getMontant();

                    // Enregistrement
                    $em->persist($mouvement);
                }
            }
        }

        // Persistance des données
        $em->flush();

        // Indicateurs
        $mouvements = $handler->getMouvements();
        $mouvementsCount = count($mouvements);
        $output->writeln("<info>$i mouvements importés sur $mouvementsCount pour une balance de $balance</info>");
    }

    /**
     * Récupère la lignée d'une catégorie, récursivement.
     *
     * @param Categorie|null $categorie
     * @param array $array
     *
     * @return array
     */
    private function getCategoriesLine($categorie, $array)
    {
        if ($categorie !== null) {
            $categorieParente = $categorie->getCategorieParente();
            if ($categorieParente !== null) {
                array_unshift($array, $categorieParente);
                $array = $this->getCategoriesLine($categorieParente, $array);
            }
        }

        return $array;
    }
}
