<?php

namespace ComptesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Script utilitaire permettant de compter le nombre d'occurences des mots d'un
 * fichier texte. Il permet d'en extraire une liste de mots-clés récurrents.
 */
class MouvementsFrequencyImportCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('comptes:import:mouvements:frequency');
        $this->setDescription("Compte le nombre d'occurences des mots d'un fichier.");
        $this->addArgument('filename', InputArgument::REQUIRED, "Un fichier texte.");
    }

    /**
     * {@inheritdoc}
     *
     * @todo : ne pas renvoyer des exceptions HTTP
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('filename');

        if (!file_exists($filename)) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Le fichier $filename n'existe pas.");
        }

        $string = file_get_contents($filename);

        if (!is_string($string)) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Échec de lecture du fichier $filename.");
        }

        $words = array_count_values(str_word_count($string, 1));
        arsort($words);

        foreach ($words as $word => $count) {
            if ($count > 1) {
                $output->writeln("{$count} => {$word}");
            }
        }

        return 0;
    }
}
