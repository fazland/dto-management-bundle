<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\DependencyInjection\Compiler;

use Fazland\DtoManagementBundle\Serializer\EventSubscriber\DtoProxySubscriber;
use Fazland\DtoManagementBundle\Utils\ClassUtils;
use Kcs\Serializer\SerializerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DtoProxySerializerPass implements CompilerPassInterface
{
    /**
     * @var ClassUtils|null
     */
    private $classUtils;

    public function __construct(?ClassUtils $classUtils = null)
    {
        $this->classUtils = $classUtils ?? new ClassUtils();
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if ($this->classUtils->interfaceExists(SerializerInterface::class)) {
            return;
        }

        $container->removeDefinition(DtoProxySubscriber::class);
    }
}
