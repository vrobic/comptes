<?php

namespace ComptesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use ComptesBundle\Entity\Keyword;

/**
 * Crée des mots-clés liés à des catégories depuis un fichier de mapping :
 *
 *     FREE: 22
 *     SALAIRE: 2
 *     EDF: 5
 *     MAAF: 11
 *
 * où 'FREE' est le mot-clé et '22' l'identifiant de sa catégorie.
 */
class KeywordsImportCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('comptes:import:keywords');
        $this->setDescription("Importe une liste de mots-clés en les liant à des catégories.");
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

        // Indicateurs
        $i = 0; // Nombre de mots-clés importés

        $em = $this->getContainer()->get('doctrine')->getManager();
        $categorieRepository = $em->getRepository('ComptesBundle:Categorie');

        $file = new \SplFileObject($filename);

        while (!$file->eof())
        {
            $line = $file->fgets();

            list ($word, $categorieID) = explode(':', $line);

            $word = trim($word);
            $categorieID = (int) $categorieID;

            $categorie = $categorieRepository->find($categorieID);

            if ($categorie === null)
            {
                throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("La catégorie n°$categorieID est inconnue.");
            }

            $keyword = new Keyword();
            $keyword->setWord($word);
            $keyword->setCategorie($categorie);

            // Indicateurs
            $i++;

            // Enregistrement
            $em->persist($keyword);
        }

        // Persistance des données
        $em->flush();

        // Indicateurs
        $output->writeln("<info>$i mots-clés importés</info>");
    }
}
