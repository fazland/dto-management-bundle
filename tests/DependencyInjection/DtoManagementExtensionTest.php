<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\DependencyInjection;

use Fazland\DtoManagementBundle\DependencyInjection\Compiler\FindModelClassesPass;
use Fazland\DtoManagementBundle\DependencyInjection\DtoManagementExtension;
use Fazland\DtoManagementBundle\Model\Finder\ServiceLocatorRegistry;
use phpmock\functions\FixedValueFunction;
use phpmock\spy\Spy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DtoManagementExtensionTest extends TestCase
{
    /**
     * * @throws \phpmock\MockEnabledException
     */
    public function testLoadShouldAddTwoDifferentCompilerPassInstances(): void
    {
        $config = [
            [
                'namespaces' => [
                    [
                        'namespace' => 'Fazland\\DtoManagementBundle\\Tests\\Fixtures\\DependencyInjection\\Model',
                        'base_dir' => 'path/to/first/dir',
                    ],
                    [
                        'namespace' => 'Fazland\\DtoManagementBundle\\Tests\\Fixtures\\DependencyInjection\\Model',
                        'base_dir' => 'path/to/second/dir',
                    ],
                ],
            ]
        ];


        $mock = new Spy(
            'Fazland\DtoManagementBundle\Model\Finder',
            'realpath',
            function ($value) {
                switch ($value) {
                    case 'path/to/first/dir/Interfaces/':
                        return realpath(__DIR__.'/../Fixtures/DependencyInjection/Model/Interfaces');

                    default:
                        return realpath(__DIR__.'/../Fixtures/DependencyInjection/Model');
                }
            }
        );
        $mock->enable();

        $container = new ContainerBuilder();
        $definition = $container->register(ServiceLocatorRegistry::class);

        $extension = new DtoManagementExtension();
        $extension->load($config, $container);
        dump($definition->getArguments()[0]);
    }
}
