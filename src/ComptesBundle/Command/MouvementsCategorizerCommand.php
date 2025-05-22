<?php

namespace ComptesBundle\Command;

use ComptesBundle\Entity\Categorie;
use ComptesBundle\Entity\Repository\MouvementRepository;
use ComptesBundle\Service\MouvementCategorizer;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question;

/**
 * Script à usage unique pour déterminer automatiquement
 * la catégorie des mouvements non catégorisés.
 */
class MouvementsCategorizerCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('comptes:mouvements:categorizer');
        $this->setDescription("Définit automatiquement la catégorie des mouvements non catégorisés.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');

        /** @var RegistryInterface $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        /** @var MouvementRepository $mouvementRepository */
        $mouvementRepository = $em->getRepository('ComptesBundle:Mouvement');

        // Indicateurs
        $i = 0; // Nombre de mouvements modifiés

        // Les mouvements non catégorisés
        $mouvements = $mouvementRepository->findBy(['categorie' => null]);

        if ($mouvements) {
            /**
             * Service de catégorisation automatique des mouvements.
             *
             * @var MouvementCategorizer $mouvementCategorizer
             */
            $mouvementCategorizer = $this->getContainer()->get('comptes_bundle.mouvement.categorizer');

            foreach ($mouvements as $mouvement) {
                $output->writeln("<comment>Mouvement : {$mouvement}</comment>");

                $categories = $mouvementCategorizer->getCategories($mouvement);
                $categorie = null;

                if ($categories) {
                    $categorieKey = 0; // La clé de la catégorie au sein du tableau $categories

                    // S'il y a plus d'une catégorie, on laisse le choix
                    if (count($categories) > 1) {
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

                        $categorieKey = $questionHelper->ask($input, $output, $question);
                    }

                    if (strtolower($categorieKey) !== 'n') { // Réponse insensible à la casse
                        $categorie = $categories[$categorieKey];
                        $mouvement->setCategorie($categorie);

                        // Enregistrement
                        $em->persist($mouvement);

                        // Indicateurs
                        $i++;
                    }
                }

                $output->writeln($categorie instanceof Categorie ?
                    "<info>\tCatégorisé en : {$categorie}</info>" :
                    "<comment>\tNon catégorisé</comment>"
                );
            }

            // Persistance des données
            $em->flush();
        } else {
            $output->writeln("<comment>\tAucun mouvement non catégorisé</comment>");
        }

        // Indicateurs
        $mouvementsCount = count($mouvements);
        $output->writeln("<info>{$i} mouvements modifiés sur {$mouvementsCount} mouvements non catégorisés</info>");

        return 0;
    }
}
