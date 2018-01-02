<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\DependencyInjection\Compiler;

use Fazland\DtoManagementBundle\Model\Finder\Finder;
use Fazland\DtoManagementBundle\Model\Finder\ServiceLocator;
use Fazland\DtoManagementBundle\Model\Finder\ServiceLocatorRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FindModelClassesPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $baseDir;

    public function __construct(string $namespace, string $baseDir)
    {
        if ('\\' !== substr($namespace, -1)) {
            $namespace .= '\\';
        }

        if (DIRECTORY_SEPARATOR !== substr($baseDir, -1)) {
            $baseDir .= DIRECTORY_SEPARATOR;
        }

        $this->namespace = $namespace;
        $this->baseDir = $baseDir;
    }

    public function process(ContainerBuilder $container)
    {
        $interfaces = Finder::findClasses($container, $this->namespace.'Interfaces\\', $this->baseDir.'Interfaces/');
        $models = array_fill_keys($interfaces, []);

        $classes = Finder::findClasses($container, $this->namespace, $this->baseDir);
        foreach ($classes as $class) {
            if (! preg_match('/^'.str_replace('\\', '\\\\', $this->namespace).'v\d+\\\\v(\d{8})\\\\/', $class, $m)) {
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
}
