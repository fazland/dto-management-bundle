<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\DependencyInjection\Compiler;

use Fazland\DtoManagementBundle\DependencyInjection\Compiler\DtoProxySerializerPass;
use Fazland\DtoManagementBundle\Serializer\EventSubscriber\DtoProxySubscriber;
use phpmock\Mock;
use phpmock\spy\Spy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DtoProxySerializerPassTest extends TestCase
{
    /**
     * @var DtoProxySerializerPass
     */
    private $pass;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->pass = new DtoProxySerializerPass();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        Mock::disableAll();
    }

    public function testProcessShouldNotActIfSerializerIsRequired(): void
    {
        $mock = new Spy(
            'Fazland\DtoManagementBundle\DependencyInjection\Compiler',
            'interface_exists',
            function (string $interface) {
                return true;
            }
        );

        $mock->enable();

        $container = $this->prophesize(ContainerBuilder::class);
        $container->removeDefinition(Argument::cetera())->shouldNotBeCalled();

        $this->pass->process($container->reveal());
    }

    public function testProcessShouldRemoveSubscriberTagIfSerializerIsNotRequired(): void
    {
        $mock = new Spy(
            'Fazland\DtoManagementBundle\DependencyInjection\Compiler',
            'interface_exists',
            function (string $interface) {
                return false;
            }
        );

        $mock->enable();

        $container = $this->prophesize(ContainerBuilder::class);
        $container->removeDefinition(DtoProxySubscriber::class)->shouldBeCalled();

        $this->pass->process($container->reveal());
    }
}
