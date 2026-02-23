<?php
declare(strict_types=1);

namespace App\Kstrwbry\BaseBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class KstrwbryBaseExtension extends Extension
{
    public function __construct(
        protected readonly string $alias,
        protected readonly string $bundleDir,
        protected readonly array $bundleConfigurations = []
    ) {}

    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->loadConfigurations($container, $configs);
        $this->loadServices($container);
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    protected function loadConfigurations(ContainerBuilder $container, array $configs): void
    {
        $newConfigurations = [];
        foreach($this->bundleConfigurations as $configurationClass) {
            if(!class_exists($configurationClass)) {
                continue;
            }

            $newConfigurations = array_merge(
                $newConfigurations,
                $this->processConfiguration(new $configurationClass($this->getAlias()), $configs)
            );
        }

        $container->setParameter(
            $this->alias,
            $newConfigurations,
        );
    }

    protected function loadServices(ContainerBuilder $container): void
    {
        $dir = $this->bundleDir . '/Resources/config';
        $servicesFilename = 'services.yaml';
        $servicesFilepath = $dir . '/' . $servicesFilename;

        if(!is_file($servicesFilepath)) {
            return;
        }

        $loader = new YamlFileLoader($container, new FileLocator($dir));
        $loader->load($servicesFilename);
    }
}
