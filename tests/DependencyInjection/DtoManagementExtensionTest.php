<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\DependencyInjection;

use Fazland\DtoManagementBundle\DependencyInjection\DtoManagementExtension;
use Fazland\DtoManagementBundle\Finder\ServiceLocatorRegistry;
use Fazland\DtoManagementBundle\Tests\Fixtures\DependencyInjection\Model\Interfaces\UserInterface;
use Fazland\DtoManagementBundle\Tests\Fixtures\DependencyInjection\Model\v2017\v20171215\User;
use Fazland\DtoManagementBundle\Tests\Fixtures\Proxy\SemVerModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DtoManagementExtensionTest extends TestCase
{
    public function testLoadShouldCreateModelServices(): void
    {
        $config = [
            [
                'namespaces' => [
                    'Fazland\\DtoManagementBundle\\Tests\\Fixtures\\DependencyInjection\\Model',
                    'Fazland\\DtoManagementBundle\\Tests\\Fixtures\\DependencyInjection\\FooModel',
                ],
            ],
        ];

        $container = new ContainerBuilder();

        $extension = new DtoManagementExtension();
        $extension->load($config, $container);

        $definition = $container->getDefinition(ServiceLocatorRegistry::class);

        self::assertCount(1, $definition->getArguments());
        self::assertCount(2, $arg = $definition->getArgument(0));
        self::assertArrayHasKey(UserInterface::class, $arg);
        self::assertInstanceOf(ServiceClosureArgument::class, $arg[UserInterface::class]);

        $reference = $arg[UserInterface::class]->getValues()[0];
        self::assertTrue($container->hasDefinition((string) $reference));
        $definition = $container->getDefinition((string) $reference);
        self::assertEquals([
            20171215 => new ServiceClosureArgument(new Reference(User::class)),
        ], $definition->getArgument(0));
    }

    public function testLoadShouldCreateModelServicesForSemVer(): void
    {
        $config = [
            [
                'namespaces' => [
                    'Fazland\\DtoManagementBundle\\Tests\\Fixtures\\Proxy\\SemVerModel',
                ],
            ],
        ];

        $container = new ContainerBuilder();

        $extension = new DtoManagementExtension();
        $extension->load($config, $container);

        $definition = $container->getDefinition(ServiceLocatorRegistry::class);

        self::assertCount(1, $definition->getArguments());
        self::assertCount(2, $arg = $definition->getArgument(0));
        self::assertArrayHasKey(SemVerModel\Interfaces\UserInterface::class, $arg);
        self::assertInstanceOf(ServiceClosureArgument::class, $arg[SemVerModel\Interfaces\UserInterface::class]);

        $reference = $arg[SemVerModel\Interfaces\UserInterface::class]->getValues()[0];
        self::assertTrue($container->hasDefinition((string) $reference));
        $definition = $container->getDefinition((string) $reference);
        self::assertEquals([
            '1.0' => new ServiceClosureArgument(new Reference(SemVerModel\v1\v1_0\User::class)),
            '1.1' => new ServiceClosureArgument(new Reference(SemVerModel\v1\v1_1\User::class)),
            '1.2' => new ServiceClosureArgument(new Reference(SemVerModel\v1\v1_2\User\User::class)),
            '2.0.alpha.1' => new ServiceClosureArgument(new Reference(SemVerModel\v2\v2_0_alpha_1\User::class)),
        ], $definition->getArgument(0));

        $versions = $container->getParameter('dto_management.versions');
        \usort($versions, 'version_compare');
        self::assertEquals(['1.0', '1.1', '1.2', '2.0-alpha.1'], $versions);
    }
}
