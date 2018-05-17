<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\DependencyInjection;

use Fazland\DtoManagementBundle\Finder\ServiceLocator;
use Fazland\DtoManagementBundle\Finder\ServiceLocatorRegistry;
use Kcs\ClassFinder\Finder\ComposerFinder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\ClassExistenceResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class DtoManagementExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.xml');
        $loader->load('proxies.xml');

        /** @var Definition[] $locators */
        $locators = [];
        foreach ($this->process($container, $config['namespaces']) as $interface => $definition) {
            if (in_array($interface, $config['exclude'])) {
                continue;
            }

            if (isset($locators[$interface])) {
                // How can this case be possible?!
                $arguments = array_merge($locators[$interface]->getArgument(0), $definition->getArgument(0));
                $locators[$interface]->setArguments([array_values(array_combine($arguments, $arguments))]);
            } else {
                $locators[$interface] = $definition;
            }
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

    /**
     * Processes namespaces and yield Service locator definitions.
     * Interface names are the keys.
     *
     * @param ContainerBuilder $container
     * @param array            $namespaces
     *
     * @return \Generator|Definition[]
     */
    private function process(ContainerBuilder $container, array $namespaces): \Generator
    {
        foreach ($namespaces as $namespace) {
            yield from $this->processNamespace($container, $namespace);
        }
    }

    /**
     * Searches through the base dir recursively for interfaces and their implemetations.
     *
     * @param ContainerBuilder $container
     * @param string           $namespace
     *
     * @return Definition[]
     */
    private function processNamespace(ContainerBuilder $container, string $namespace): array
    {
        $finder = new ComposerFinder();
        $finder->inNamespace($namespace);

        $classes = iterator_to_array($finder);
        $interfaces = array_filter($classes, function (\ReflectionClass $class) {
            return $class->isInterface();
        });

        $modelsByInterface = [];

        foreach ($interfaces as $interface => $unused) {
            $modelsByInterface[$interface] = $this->processInterface($container, $interface, $namespace, $classes);
        }

        $locators = [];
        foreach ($modelsByInterface as $interface => $versions) {
            $definition = new Definition(ServiceLocator::class, [$versions]);
            $definition->addTag('container.service_locator');
            $locators[$interface] = $definition;
        }

        return $locators;
    }

    private function processInterface(ContainerBuilder $container, string $interface, string $namespace, array $classes): array
    {
        $container->addResource(new ClassExistenceResource($interface, true));
        $models = [];

        foreach ($classes as $class => $reflector) {
            if (! $reflector->isInstantiable() || ! $reflector->isSubclassOf($interface)) {
                continue;
            }

            $container->addResource(new ClassExistenceResource($class, true));
            if (! preg_match('/^'.str_replace('\\', '\\\\', $namespace).'\\\\v\d+\\\\v(\d{8})\\\\/', $class, $m)) {
                continue;
            }

            $models[$m[1]] = new Reference($reflector->getName());
        }

        return $models;
    }
}
