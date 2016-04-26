<?php

namespace ComptesBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Charge la configuration du bundle.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class ComptesExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        /**
         * @var array
         */
        $configuration = $this->processConfiguration(new Configuration(), $configs);

        // Injecte la configuration dans les parameters
        $rootName = 'comptes';
        $container->setParameter($rootName, $configuration);
        $this->setConfigAsParameters($container, $configuration, $rootName);

        // Charge les fichiers de configuration personnalisés
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * Injecte la configuration dans les parameters.
     * Permet d'accéder aux valeurs de la configuration
     * comme si elles étaient définies dans les parameters.
     *
     * @param ContainerBuilder $container
     * @param array $params
     * @param string $rootName
     *
     * @return void
     */
    private function setConfigAsParameters(ContainerBuilder &$container, array $params, $rootName)
    {
        foreach ($params as $key => $value) {

            $name = "$rootName.$key";
            $container->setParameter($name, $value);

            if (is_array($value)) {
                $this->setConfigAsParameters($container, $value, $name);
            }
        }
    }
}
