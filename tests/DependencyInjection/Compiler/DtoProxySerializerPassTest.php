<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\DependencyInjection\Compiler;

use Fazland\DtoManagementBundle\DependencyInjection\Compiler\DtoProxySerializerPass;
use Fazland\DtoManagementBundle\Serializer\EventSubscriber\DtoProxySubscriber;
use Fazland\DtoManagementBundle\Utils\ClassUtils;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DtoProxySerializerPassTest extends TestCase
{
    /**
     * @var ClassUtils|ObjectProphecy
     */
    private $classUtils;

    /**
     * @var DtoProxySerializerPass
     */
    private $pass;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->classUtils = $this->prophesize(ClassUtils::class);
        $this->pass = new DtoProxySerializerPass($this->classUtils->reveal());
    }

    public function testProcessShouldNotActIfSerializerIsRequired(): void
    {
        $this->classUtils->interfaceExists(Argument::type('string'))->willReturn(true);

        $container = $this->prophesize(ContainerBuilder::class);
        $container->removeDefinition(Argument::cetera())->shouldNotBeCalled();

        $this->pass->process($container->reveal());
    }

    public function testProcessShouldRemoveSubscriberTagIfSerializerIsNotRequired(): void
    {
        $this->classUtils->interfaceExists(Argument::type('string'))->willReturn(false);

        $container = $this->prophesize(ContainerBuilder::class);
        $container->removeDefinition(DtoProxySubscriber::class)->shouldBeCalled();

        $this->pass->process($container->reveal());
    }
}
