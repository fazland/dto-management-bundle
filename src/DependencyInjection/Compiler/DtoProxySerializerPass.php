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

        $definition = $container->getDefinition(DtoProxySubscriber::class);
        $definition->clearTag('kernel.event_subscriber');

        $container->setDefinition(DtoProxySubscriber::class, $definition);
    }
}
