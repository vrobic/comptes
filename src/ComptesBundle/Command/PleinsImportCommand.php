<?php

namespace ComptesBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question;

/**
 * Script d'import de pleins de carburant depuis un fichier.
 */
class PleinsImportCommand extends AbstractImportCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('comptes:import:pleins');
        $this->setDescription("Importe des pleins de carburant depuis un fichier.");
        $this->addArgument('filename', InputArgument::REQUIRED, "Fichier depuis lequel importer les pleins.");
        $this->addArgument('handler', InputArgument::REQUIRED, "Handler à utiliser.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');
        $interaction = !$input->getOption('no-interaction');
        $filename = $input->getArgument('filename');
        $handlerIdentifier = $input->getArgument('handler');

        // Définit le type d'import
        $this->setType('pleins');

        // Chargement de la configuration
        $this->loadConfiguration();

        // Parsing du fichier
        $handler = $this->getHandler($handlerIdentifier);
        $splFile = $this->getFile($filename);
        $handler->parse($splFile);

        // Le manager d'entités qui va nous servir à persister les pleins
        $em = $this->getContainer()->get('doctrine')->getManager();

        // Indicateurs
        $i = 0; // Nombre de pleins importés

        // 1. Les pleins valides
        $validPleins = $handler->getValidPleins();

        if ($validPleins) {

            $output->writeln("<info>Pleins valides</info>", OutputInterface::VERBOSITY_VERBOSE);

            foreach ($validPleins as $plein) {

                $output->writeln("<comment>$plein</comment>");

                // Indicateurs
                $i++;

                // Enregistrement
                $em->persist($plein);
            }
        }

        // 2. Les pleins suspectés comme doublons, qui nécessitent une confirmation manuelle
        if ($interaction) {

            $waitingPleins = $handler->getWaitingPleins();

            foreach ($waitingPleins as $plein) {

                $output->writeln("<info>Pleins à valider</info>", OutputInterface::VERBOSITY_VERBOSE);

                $output->writeln("<comment>$plein</comment>");

                $confirm = $questionHelper->ask(
                    $input,
                    $output,
                    new Question\ConfirmationQuestion("<question>Un plein similaire existe déjà :\n\t$plein\nImporter (y/N) ?</question>", false)
                );

                if ($confirm) {

                    // Indicateurs
                    $i++;

                    // Enregistrement
                    $em->persist($plein);
                }
            }
        }

        // Persistance des données
        $em->flush();

        // Indicateurs
        $pleins = $handler->getPleins();
        $pleinsCount = count($pleins);
        $output->writeln("<info>$i pleins importés sur $pleinsCount</info>");
    }
}
