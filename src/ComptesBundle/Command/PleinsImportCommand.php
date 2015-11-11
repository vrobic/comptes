<?php

namespace ComptesBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class PleinsImportCommand extends ImportCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('comptes:import:pleins');
        $this->setDescription("Importe des pleins de carburant depuis un fichier.");
        $this->addArgument('filename', InputArgument::REQUIRED, "Fichier depuis lequel importer les pleins.");
        $this->addArgument('handler', InputArgument::REQUIRED, "Handler à utiliser.");
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        $verbose = $input->getOption('verbose');
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

            $verbose && $output->writeln("<info>Pleins valides</info>");

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

                $verbose && $output->writeln("<info>Pleins à valider</info>");

                $output->writeln("<comment>$plein</comment>");

                $confirm = $dialog->askConfirmation($output, "<question>Un plein similaire existe déjà :\n\t$plein\nImporter (y/N) ?</question>", false);

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
