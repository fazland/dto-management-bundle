<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\DependencyInjection;

use Fazland\DtoManagementBundle\Model\Finder\Finder;
use Fazland\DtoManagementBundle\Model\Finder\ServiceLocator;
use Fazland\DtoManagementBundle\Model\Finder\ServiceLocatorRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class DtoManagementExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        foreach ($config['namespaces'] as $value) {
            $namespace = $value['namespace'];
            $baseDir = $value['base_dir'];

            if ('\\' !== substr($namespace, -1)) {
                $namespace .= '\\';
            }

            if (DIRECTORY_SEPARATOR !== substr($baseDir, -1)) {
                $baseDir .= DIRECTORY_SEPARATOR;
            }

            $this->process($container, $namespace, $baseDir);
        }
    }

    private function process(ContainerBuilder $container, string $namespace, string $baseDir): void
    {
        $interfaces = Finder::findClasses($container, $namespace.'Interfaces\\', $baseDir.'Interfaces/');
        $models = array_fill_keys($interfaces, []);

        $classes = Finder::findClasses($container, $namespace, $baseDir);

        foreach ($classes as $class) {
            if (! preg_match('/^'.str_replace('\\', '\\\\', $namespace).'v\d+\\\\v(\d{8})\\\\/', $class, $m)) {
                continue;
            }

            $r = $container->getReflectionClass($class);
            foreach ($r->getInterfaceNames() as $interfaceName) {
                if (array_key_exists($interfaceName, $models)) {
                    $models[$interfaceName][$m[1]] = new Reference($r->getName());
                }
            }
        }

        $locators = [];
        foreach ($models as $interface => $versions) {
            $definition = new Definition(ServiceLocator::class, [$versions]);
            $definition->addTag('container.service_locator');
            $locators[$interface] = $definition;
        }

        $container->findDefinition(ServiceLocatorRegistry::class)
            ->setArgument(0, $locators);
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace(): string
    {
        return 'http://fazland.com/schema/dic/'.$this->getAlias();
    }

    /**
     * {@inheritdoc}
     */
    public function getXsdValidationBasePath(): string
    {
        return __DIR__.'/../Resources/config/schema';
    }
}
