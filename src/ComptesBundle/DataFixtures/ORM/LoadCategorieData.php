<?php

namespace ComptesBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ComptesBundle\Entity\Categorie;

class LoadCategorieData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $manager;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container=null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        // Chargement de la configuration
        $configurationLoader = $this->container->get('comptes_bundle.configuration.loader');
        $fixturesConfiguration = $configurationLoader->load('fixtures.yml');

        // Tableau de données
        $categoriesContent = $fixturesConfiguration['categories'];

        $this->manager = $manager;

        // Création récursive des catégories
        $this->loadCategories($categoriesContent);

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 3;
    }

    /**
     * Fonction récursive de création des catégories.
     *
     * @param array $categoriesContent Le contenu de la catégorie
     * @param Categorie $categorieParente La catégorie parente
     */
    private function loadCategories($categoriesContent, $categorieParente=null)
    {
        foreach ($categoriesContent as $categorieContent) {

            $categorie = new Categorie();

            $categorie->setNom($categorieContent['nom']);
            $categorie->setCategorieParente($categorieParente);
            $categorie->setRang($categorieContent['rang']);

            $this->manager->persist($categorie);

            if (!empty($categorieContent['subcategories'])) {

                $subCategoriesContent = $categorieContent['subcategories'];

                $this->loadCategories($subCategoriesContent, $categorie);
            }
        }
    }
}
