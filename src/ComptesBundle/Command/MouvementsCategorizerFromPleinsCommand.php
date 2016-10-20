<?php

namespace ComptesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question;

/**
 * Script à usage unique pour définir la catégorie de tous les mouvements
 * correspondants à des pleins.
 */
class MouvementsCategorizerFromPleinsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('comptes:mouvements:categorizer:from:pleins');
        $this->setDescription("Définit la catégorie de tous les mouvements correspondants à des pleins.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');

        $em = $this->getContainer()->get('doctrine')->getManager();
        $pleinRepository = $em->getRepository('ComptesBundle:Plein');
        $mouvementRepository = $em->getRepository('ComptesBundle:Mouvement');
        $categorieRepository = $em->getRepository('ComptesBundle:Categorie');

        // Tous les pleins
        $pleins = $pleinRepository->findAll();

        // La catégorie "Carburant"
        $categorieId = 9; // @todo : rendre paramétrable
        $categorie = $categorieRepository->find($categorieId);

        // Indicateurs
        $i = 0; // Nombre de mouvements modifiés

        foreach ($pleins as $plein) {

            $output->writeln("<comment>Plein : {$plein}</comment>");

            // Recherche du mouvement correspondant au plein
            $montant = -$plein->getMontant();
            $interval = new \DateInterval('P7D'); // 7 jours
            $dateString = $plein->getDate()->format('Ymd');
            $dateStart = new \DateTime($dateString);
            $dateEnd = new \DateTime($dateString);
            $dateStart->sub($interval);
            $dateEnd->add($interval);

            // Les mouvements correspondants au plein, à plus ou moins 7 jours
            $mouvements = $mouvementRepository->findByMontantBetweenDates($montant, $dateStart, $dateEnd);

            if ($mouvements) {

                foreach ($mouvements as $mouvement) {

                    // Si le mouvement n'est pas déjà dans la bonne catégorie
                    $previousCategorie = $mouvement->getCategorie();
                    if ($previousCategorie !== null && $previousCategorie->getId() === $categorieId) {
                        continue;
                    }

                    $output->writeln("<comment>\tMouvement : {$mouvement}</comment>");

                    // Question à l'utilisateur
                    $question = new Question\ConfirmationQuestion("<question>\tRecatégoriser en \"{$categorie}\" (y/N) ?</question>", false);

                    $confirm = $questionHelper->ask($input, $output, $question);

                    if ($confirm) {

                        // Enregistrement
                        $mouvement->setCategorie($categorie);
                        $em->persist($mouvement);

                        // Indicateurs
                        $i++;
                    }
                }
            } else {
                $output->writeln("<comment>\tAucun mouvement correspondant</comment>");
            }
        }

        // Persistance des données
        $em->flush();

        // Indicateurs
        $pleinsCount = count($pleins);
        $output->writeln("<info>{$i} mouvements modifiés sur {$pleinsCount} pleins</info>");
    }
}
