<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\Fixtures\DependencyInjection;

use Fazland\DtoManagementBundle\DtoManagementBundle;
use Fazland\DtoManagementBundle\Tests\Fixtures\TestKernel;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
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
    protected function getKernelParameters(): array
    {
        return parent::getKernelParameters() + [
            'kernel.root_dir' => __DIR__,
        ];
    }
}
