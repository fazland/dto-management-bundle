<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle;

use Fazland\DtoManagementBundle\DependencyInjection\Compiler\AddInterceptorsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class DtoManagementBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container
            ->addCompilerPass(new AddInterceptorsPass());
    }

    public function boot()
    {
        $cacheDir = $this->container->getParameter('kernel.cache_dir');

        $classMap = require $cacheDir.'/dto-proxies-map.php';
        spl_autoload_register(function (string $className) use (&$classMap): bool {
            if (! isset($classMap[$className])) {
                return false;
            }

            require $classMap[$className];

            return true;
        });
    }
}
