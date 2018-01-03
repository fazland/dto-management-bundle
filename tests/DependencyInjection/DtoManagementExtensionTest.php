<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\DependencyInjection;

use Fazland\DtoManagementBundle\DependencyInjection\DtoManagementExtension;
use Fazland\DtoManagementBundle\Finder\ServiceLocatorRegistry;
use Fazland\DtoManagementBundle\Tests\Fixtures\DependencyInjection\Model\Interfaces\UserInterface;
use Fazland\DtoManagementBundle\Tests\Fixtures\DependencyInjection\Model\v2017\v20171215\User;
use phpmock\Mock;
use phpmock\spy\Spy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DtoManagementExtensionTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        Mock::disableAll();
    }

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
                        'namespace' => 'Fazland\\DtoManagementBundle\\Tests\\Fixtures\\DependencyInjection\\FooModel',
                        'base_dir' => 'path/to/second/dir',
                    ],
                ],
            ],
        ];

        $mock = new Spy(
            'Fazland\DtoManagementBundle\Finder',
            'realpath',
            function ($value): string {
                switch ($value) {
                    case 'path/to/first/dir/Interfaces/':
                        return realpath(__DIR__.'/../Fixtures/DependencyInjection/Model/Interfaces');

                    case 'path/to/first/dir/':
                        return realpath(__DIR__.'/../Fixtures/DependencyInjection/Model');

                    case 'path/to/second/dir/Interfaces/':
                        return realpath(__DIR__.'/../Fixtures/DependencyInjection/FooModel/Interfaces');

                    case 'path/to/second/dir/':
                        return realpath(__DIR__.'/../Fixtures/DependencyInjection/FooModel');
                }
            }
        );

        $mock->enable();

        $container = new ContainerBuilder();

        $extension = new DtoManagementExtension();
        $extension->load($config, $container);

        $definition = $container->getDefinition(ServiceLocatorRegistry::class);

        $this->assertCount(1, $definition->getArguments());
        $this->assertCount(2, $arg = $definition->getArgument(0));
        $this->assertArrayHasKey(UserInterface::class, $arg);
        $this->assertInstanceOf(Definition::class, $arg[UserInterface::class]);

        $this->assertEquals([
            20171215 => new Reference(User::class),
        ], $arg[UserInterface::class]->getArgument(0));
    }
}
