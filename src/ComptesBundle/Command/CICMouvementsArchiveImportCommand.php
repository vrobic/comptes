<?php

namespace ComptesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question;
use ComptesBundle\Entity\Mouvement;

/**
 * Procédure d'import de relevés de compte CIC au format PDF.
 *
 * 1. Télécharger le relevé de comptes sur le site web du CIC
 * 2. En extraire le texte : pdftotext comptes.pdf -layout
 * 3. Nettoyer le fichier comptes.txt obtenu de ses données inutiles,
 *    et ne garder que deux types de lignes :
 *        - les lignes qui signalent un compte
 *        - les mouvements
 *    Le fichier doit ressembler à ceci :
 *
 *    €         C/C CONTRAT PERSONNEL PARCOURS J N° ########### en euros
 *    05/10/2012 05/10/2012 PRLV AXA MUTUELLE                                         32,65
 *    08/10/2012 08/10/2012 PAIEMENT CB 0510 ST SEBASTIEN                                17,12
 *                          SUPER U ST SEB CARTE
 *    €     C/C CONTRAT PERSONNEL PARCOURS J N° ########### en euros
 *    23/10/2012 23/10/2012 PRLV ORANGE FRANCE SA                                      29,00
 *                          MOBILE
 *    02/11/2012 02/11/2012 VIR LOYER NOVEMBRE + EAU                                570,00
 *    €     LIVRET JEUNE N° ########### en euros
 *    21/10/2012 01/11/2012 VIR FB DE C/C CONTRAT PERSONNEL                           75,00
 *
 *    A noter :
 *        - le script ne tient pas compte des lignes vides
 *        - il recherche "€ N° ###########" pour identifier le numéro du compte sur 11 chiffres
 *        - et "00-00-0000 00-00-0000" pour identifier un mouvement
 *        - l'ordre des dates est le suivant : date d'opération, date de valeur
 *        - la description d'un mouvement doit débuter un espace après les dates
 *        - la description peut s'étendre sur 5 lignes qui doivent toutes être alignées par la gauche
 *        - les montants n'ont pas besoin d'être alignés dans une colonne
 *        - le montant doit se trouver au moins 15 espaces après une des lignes de description (la première idéalement)
 *
 * 4. Le script gère la catégorisation automatique des mouvements,
 *    en fonction de mots-clés trouvés dans la description des mouvements.
 *    La liste des mots-clés les plus courants peut être obtenue en exécutant la commande suivante :
 *        php app/console comptes:import:mouvements:frequency comptes.txt > words.txt
 *    Le fichier words.txt obtenu contient la liste des mots utilisés,
 *    classés par fréquence d'apparition dans le relevé de comptes.
 *    Un traitement manuel de cette liste doit être effectué
 *    pour ne garder que les mots-clés exploitables.
 */
class CICMouvementsArchiveImportCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('comptes:import:mouvements:archive:cic');
        $this->setDescription("Importe les mouvements d'un compte bancaire du CIC.");
        $this->addArgument('filename', InputArgument::REQUIRED, "Fichier texte depuis lequel importer les mouvements.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');
        $filename = $input->getArgument('filename');

        if (!file_exists($filename)) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Le fichier $filename n'existe pas.");
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $compteRepository = $em->getRepository('ComptesBundle:Compte');

        $lines = file($filename);

        // Indicateurs
        $i = 0; // Nombre de mouvements importés
        $balance = 0; // Balance des mouvements (crédit ou débit)

        // Numéro de compte
        $numeroCompte = null;

        foreach ($lines as $lineNumber => $line) {
            // Recherche la présence du numéro de compte, signalé par "€ N° ###########"
            preg_match('/€.+[N°|N˚]\s(\d{11})/', $line, $matches);

            if (isset($matches[1])) {
                $numeroCompteRaw = $matches[1];
                $numeroCompte = ltrim($numeroCompteRaw, "0");
            }

            // Si le numéro de compte n'est pas déterminé, la ligne ne sera pas exploitable
            if (null === $numeroCompte) {
                continue;
            }

            $compte = $compteRepository->findOneBy(['numero' => $numeroCompte]);

            if (!$compte) {
                throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Le compte n°$numeroCompte est inconnu.");
            }

            // Recherche la présence des dates d'opération et de valeur, format "00-00-0000 00-00-0000"
            preg_match('/(\d{2}\/\d{2}\/\d{4})\s{1}\d{2}\/\d{2}\/\d{4}/', $line, $matches, PREG_OFFSET_CAPTURE);

            // S'il n'y en a pas, la ligne ne concerne pas un mouvement
            if (!isset($matches[1])) {
                continue;
            }

            // La date brute et sa position dans la ligne
            $dateRaw = $matches[1][0];
            $datePos = $matches[1][1];

            // L'objet DateTime, utilisable
            $date = \DateTime::createFromFormat('d/m/Y', $dateRaw);

            // La description démarre 22 caractères après la date
            $descriptionPos = $datePos + 22; // 22 => "00-00-0000 00-00-0000 "

            // Recherche de la description et du montant sur la ligne de la date (0) et les 4 du dessous
            $descriptionRows = [];
            $montant = null;

            for ($nextLineOffset = 0; $nextLineOffset <= 4; $nextLineOffset++) {
                $nextLineNumber = $lineNumber + $nextLineOffset;

                if (!isset($lines[$nextLineNumber])) {
                    break;
                }

                $nextLine = $lines[$nextLineNumber];

                // Les lignes suivantes ne peuvent contenir que des espaces avant la description
                if ($nextLineOffset > 0) {
                    $nextLineLength = strlen($nextLine);

                    if ($nextLineLength < $descriptionPos) {
                        break;
                    }

                    $spacesCount = substr_count($nextLine, " ", 0, $descriptionPos);

                    if ($spacesCount !== $descriptionPos) {
                        break;
                    }
                }

                // La description se termine lorsqu'au moins ~15 espaces sont rencontrés ou à la fin de la ligne
                $subject = substr($nextLine, $descriptionPos);
                preg_match('/(.+?)(?=\s{15,}|$)/', $subject, $matches);

                // Si elle n'a pas été trouvée, on passe à la ligne suivante
                if (!isset($matches[1])) {
                    continue;
                }

                // La description brute
                $descriptionRaw = $matches[1];
                $descriptionRows[] = $descriptionRaw;

                /* Le montant n'est présent que sur une des lignes.
                 * Donc s'il n'a pas encore été défini... */
                if (null === $montant) {
                    $descriptionLength = strlen($descriptionRaw);

                    // Il se trouve en fin de ligne, après une série d'espaces
                    $subject = substr($nextLine, $descriptionPos + $descriptionLength);
                    preg_match('/([^\s]+.+)$/', $subject, $matches);

                    if (isset($matches[1])) {
                        $montantRaw = $matches[1];
                        $montant = str_replace('.', '', $montantRaw); // Séparateur milliers
                        $montant = str_replace(',', '.', $montant); // Séparateur décimales
                    }
                }
            }

            if (!$descriptionRows) {
                throw new \Exception("La description n'a pas été trouvée à la ligne n°$lineNumber.");
            }
            if (null === $montant) {
                throw new \Exception("Le montant n'a pas été trouvé à la ligne n°$lineNumber.");
            }

            $description = implode(" ", $descriptionRows);

            $mouvement = new Mouvement();
            $mouvement->setCompte($compte);
            $mouvement->setDate($date);
            $mouvement->setDescription($description);
            $mouvement->setMontant($montant);

            $output->writeln("<comment>{$compte} {$mouvement}</comment>");

            // Question à l'utilisateur
            $question = new Question\Question("<question>S'agit-il d'un crédit ou d'un débit (c/D) ?</question>", 'd');
            $question->setValidator(function ($answer) {
                if (!in_array(strtolower($answer), ['c', 'd'])) {
                    throw new \RuntimeException("Réponse invalide");
                }

                return $answer;
            });

            $signe = $questionHelper->ask($input, $output, $question);

            if ('d' === strtolower($signe)) { // Réponse insensible à la casse
                $montant = -$montant;
                $mouvement->setMontant($montant);
            }

            // Service de catégorisation automatique des mouvements
            $mouvementCategorizer = $this->getContainer()->get('comptes_bundle.mouvement.categorizer');
            $categories = $mouvementCategorizer->getCategories($mouvement);

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
                }
            }

            $em->persist($mouvement);
            $em->flush();

            // Indicateurs
            $i++;
            $balance += $montant;
        }

        $output->writeln("<info>{$i} mouvements importés pour une balance de {$balance}€</info>");
    }
}
