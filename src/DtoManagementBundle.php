<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle;

use Fazland\DtoManagementBundle\DependencyInjection\Compiler\AddInterceptorsPass;
use Fazland\DtoManagementBundle\DependencyInjection\Compiler\DtoProxySerializerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class DtoManagementBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container
            ->addCompilerPass(new AddInterceptorsPass())
            ->addCompilerPass(new DtoProxySerializerPass())
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        $cacheDir = $this->container->getParameter('kernel.cache_dir');

        $classMap = require "$cacheDir/dto-proxies-map.php";
        \spl_autoload_register(static function (string $className) use (&$classMap): bool {
            if (! isset($classMap[$className])) {
                return false;
            }

            require $classMap[$className];

            return true;
        });
    }
}
