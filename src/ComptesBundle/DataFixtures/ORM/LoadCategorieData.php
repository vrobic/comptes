<?php

namespace ComptesBundle\DataFixtures\ORM;

use ComptesBundle\Service\ConfigurationLoader;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ComptesBundle\Entity\Categorie;

/**
 * La fixture qui crée les catégories de mouvements bancaires.
 */
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
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        /** @var ConfigurationLoader $configurationLoader */
        $configurationLoader = $this->container->get('comptes_bundle.configuration.loader');
        $fixturesConfiguration = $configurationLoader->load('fixtures');

        // Tableau de données
        $categoriesContent = $fixturesConfiguration['categories'];

        $this->manager = $manager;

        // Création récursive des catégories
        $this->loadCategories($categoriesContent);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder(): int
    {
        return 3;
    }

    /**
     * Fonction récursive de création des catégories.
     *
     * @param array<string, mixed> $categoriesContent Le contenu de la catégorie
     * @param ?Categorie           $categorieParente  La catégorie parente
     */
    private function loadCategories(array $categoriesContent, ?Categorie $categorieParente = null): void
    {
        foreach ($categoriesContent as $categorieContent) {
            $categorie = new Categorie();

            $categorie->setNom($categorieContent['nom']);
            $categorie->setCategorieParente($categorieParente);
            $categorie->setRang($categorieContent['rang']);

            $this->manager->persist($categorie);

            if (array_key_exists('subcategories', $categorieContent) && is_array($categorieContent['subcategories'])) {
                $subCategoriesContent = $categorieContent['subcategories'];

                $this->loadCategories($subCategoriesContent, $categorie);
            }
        }
    }
}
