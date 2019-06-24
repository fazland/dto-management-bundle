<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\DependencyInjection\Compiler;

use Fazland\DtoManagementBundle\InterfaceResolver\ResolverInterface;
use Fazland\DtoManagementBundle\Proxy\ProxyInterface;
use Fazland\DtoManagementBundle\VarDumper\ProxyCaster;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\VarDumper\Caster\StubCaster;

class RegisterDtoProxyCasterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('var_dumper.cloner')) {
            return;
        }

        $container->findDefinition('var_dumper.cloner')
            ->addMethodCall('addCasters', [
                [
                    ProxyInterface::class => ProxyCaster::class.'::castDtoProxy',
                    ResolverInterface::class => StubCaster::class.'::cutInternals',
                ],
            ]);
    }
}
