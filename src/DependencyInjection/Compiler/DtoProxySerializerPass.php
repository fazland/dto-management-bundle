<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\DependencyInjection\Compiler;

use Fazland\DtoManagementBundle\Serializer\EventSubscriber\DtoProxySubscriber;
use Kcs\Serializer\SerializerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DtoProxySerializerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (interface_exists(SerializerInterface::class)) {
            return;
        }

        $container->removeDefinition(DtoProxySubscriber::class);
    }
}
