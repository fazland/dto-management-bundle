<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\Fixtures\Proxy;

use Fazland\DtoManagementBundle\DtoManagementBundle;
use Fazland\DtoManagementBundle\Tests\Fixtures\TestKernel;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends TestKernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new DtoManagementBundle(),
            new SecurityBundle(),
            new AppBundle(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        static $dir = null;
        if (null === $dir) {
            $dir = \sys_get_temp_dir().DIRECTORY_SEPARATOR.\uniqid('cache', true);
            @\mkdir($dir, 0777, true);
        }

        return $dir;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir(): string
    {
        static $dir = null;
        if (null === $dir) {
            $dir = \sys_get_temp_dir().DIRECTORY_SEPARATOR.\uniqid('logs', true);
            @\mkdir($dir, 0777, true);
        }

        return $dir;
    }
}
