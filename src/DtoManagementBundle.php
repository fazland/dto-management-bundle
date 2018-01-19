<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle;

use Composer\Autoload\ClassLoader;
use Fazland\DtoManagementBundle\DependencyInjection\Compiler\AddInterceptorsPass;
use Symfony\Component\Debug\DebugClassLoader;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
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
        $loader = self::getValidLoader();
        $cacheDir = $this->container->getParameter('kernel.cache_dir');

        $loader->addClassMap(require $cacheDir.'/dto-proxies-map.php');
    }

    /**
     * Try to get a registered instance of composer ClassLoader.
     *
     * @return ClassLoader
     *
     * @throws \RuntimeException if composer CLassLoader cannot be found
     */
    private static function getValidLoader(): ClassLoader
    {
        foreach (spl_autoload_functions() as $autoload_function) {
            if (is_array($autoload_function) && $autoload_function[0] instanceof DebugClassLoader) {
                $autoload_function = $autoload_function[0]->getClassLoader();
            }

            if (is_array($autoload_function) && $autoload_function[0] instanceof ClassLoader) {
                return $autoload_function[0];
            }
        }

        throw new \RuntimeException('Cannot find a valid composer class loader in registered autoloader functions. Cannot continue.');
    }
}
