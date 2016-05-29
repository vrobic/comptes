<?php

namespace ComptesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Valide toutes les entités du bundle en les passant dans le moteur de validation.
 * Les entités testées sont celles persistées et gérées par Doctrine.
 */
class ValidateEntitiesCommand extends AbstractImportCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('comptes:validate:entities');
        $this->setDescription("Passe dans le moteur de validation toutes les entités persistées.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $validator = $container->get('validator');
        $em = $container->get('doctrine')->getManager();

        // Liste exhaustive des classes gérées par Doctrine
        $classes = $em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();

        foreach ($classes as $class) {

            $entityRepository = $em->getRepository($class);
            $entities = $entityRepository->findAll();

            $output->writeln(sprintf("<comment>Validation des %d entités de type %s.</comment>", count($entities), $class));

            foreach ($entities as $entity) {

                /**
                 * @var Symfony/Component/Validator/ConstraintViolationList
                 */
                $errors = $validator->validate($entity);

                foreach ($errors as $error) {
                    $output->writeln(sprintf("<error>\tEntité %d : %s</error>", $entity->getId(), $error->getMessage()));
                    $invalidEntitiesCount++;
                }
            }
        }
    }
}
