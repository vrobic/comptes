<?php

namespace ComptesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class MouvementsFrequencyImportCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('comptes:import:mouvements:frequency');
        $this->setDescription("Compte le nombre d'occurences des mots d'un fichier.");
        $this->addArgument('filename', InputArgument::REQUIRED, "Un fichier texte.");
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('filename');

        if (!file_exists($filename))
        {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Le fichier $filename n'existe pas.");
        }

        $string = file_get_contents($filename);
        $words = array_count_values(str_word_count($string, 1));
        arsort($words);

        foreach ($words as $word => $count)
        {
            if ($count > 1)
            {
                $output->writeln("$count => $word");
            }
        }
    }
}
