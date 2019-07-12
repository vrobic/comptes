<?php

namespace ComptesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Script d'installation de la base de données.
 */
class InstallCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('comptes:install');
        $this->setDescription("Installation de la base de données.");
        $this->addOption('drop-database', null, InputOption::VALUE_NONE, "Supprime la base de données avant de la recréer.");
        $this->addOption('load-fixtures', null, InputOption::VALUE_NONE, "Charge les fixtures une fois la base de données installée.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $in = $input;
        $returnCode = 0;

        // 1. Suppression éventuelle de la base de données
        $dropDatabase = $input->getOption('drop-database');

        if ($dropDatabase) {
            $output->writeln("<comment>Exécution de doctrine:database:drop</comment>");

            // Exécution dans un autre thread pour éviter un problème avec les autres commandes
            exec('php app/console doctrine:database:drop --force', $outputLines, $returnCode);

            foreach ($outputLines as $outputLine) {
                $output->writeln($outputLine);
            }
        }

        // 2. Création de la base de données
        if (0 === $returnCode) {
            $output->writeln("<comment>Exécution de doctrine:database:create</comment>");
            $command = $this->getApplication()->find('doctrine:database:create');
            $in = new ArrayInput(
                array(
                    'command' => 'doctrine:database:create',
                )
            );
            $returnCode = $command->run($in, $output);
        }

        // 3. Création du schéma de la base de données
        if (0 === $returnCode) {
            $output->writeln("<comment>Exécution de doctrine:schema:create</comment>");
            $command = $this->getApplication()->find('doctrine:schema:create');
            $in = new ArrayInput(
                array(
                    'command' => 'doctrine:schema:create',
                )
            );
            $returnCode = $command->run($in, $output);
        }

        // 4. Chargement éventuel des fixtures
        $loadFixtures = $input->getOption('load-fixtures');

        if ($loadFixtures && 0 === $returnCode) {
            $output->writeln("<comment>Exécution de doctrine:fixtures:load</comment>");
            $command = $this->getApplication()->find('doctrine:fixtures:load');
            $in = new ArrayInput(
                array(
                    'command' => 'doctrine:fixtures:load',
                    '--no-interaction' => true,
                )
            );
            $returnCode = $command->run($in, $output);
        }
    }
}
