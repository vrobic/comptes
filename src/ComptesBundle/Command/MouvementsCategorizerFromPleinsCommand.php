<?php

namespace ComptesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $dialog = $this->getHelperSet()->get('dialog');

        $em = $this->getContainer()->get('doctrine')->getManager();
        $pleinRepository = $em->getRepository('ComptesBundle:Plein');
        $mouvementRepository = $em->getRepository('ComptesBundle:Mouvement');
        $categorieRepository = $em->getRepository('ComptesBundle:Categorie');

        // Tous les pleins
        $pleins = $pleinRepository->findAll();

        // La catégorie "Carburant"
        $categorie = $categorieRepository->find(9);

        // Indicateurs
        $i = 0; // Nombre de mouvements modifiés

        foreach ($pleins as $plein) {

            $output->writeln("<comment>Plein : $plein</comment>");

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

                    $output->writeln("<comment>\tMouvement : $mouvement</comment>");
                    $confirm = $dialog->askConfirmation($output, "<question>\tModifier la catégorie (Y/n) ?</question>");

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
        $output->writeln("<info>$i mouvements modifiés sur $pleinsCount pleins</info>");
    }
}
