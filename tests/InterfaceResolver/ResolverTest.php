<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\InterfaceResolver;

use Fazland\DtoManagementBundle\Finder\ServiceLocator;
use Fazland\DtoManagementBundle\Finder\ServiceLocatorRegistry;
use Fazland\DtoManagementBundle\InterfaceResolver\Resolver;
use Fazland\DtoManagementBundle\Tests\Fixtures\ModelConverter\Model\Interfaces\UserInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ResolverTest extends TestCase
{
    /**
     * @var ServiceLocatorRegistry|ObjectProphecy
     */
    private $registry;

    /**
     * @var RequestStack|ObjectProphecy
     */
    private $requestStack;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->registry = $this->prophesize(ServiceLocatorRegistry::class);
        $this->requestStack = $this->prophesize(RequestStack::class);

        $this->resolver = new Resolver($this->registry->reveal(), $this->requestStack->reveal());
    }

    public function testResolveShouldThrowServiceNotFoundException(): void
    {
        $this->expectException(ServiceNotFoundException::class);

        $request = $this->prophesize(Request::class);
        $request->attributes = new ParameterBag(['_version' => '20171128']);

        $this->registry->get(UserInterface::class)
            ->willReturn($locator = $this->prophesize(ServiceLocator::class))
        ;
        $locator->get('20171128')->willThrow(new ServiceNotFoundException('20171128'));

        $this->resolver->resolve(UserInterface::class, $request->reveal());
    }

    public function testResolveShouldReturnTheService(): void
    {
        $request = $this->prophesize(Request::class);
        $request->attributes = new ParameterBag(['_version' => '20171128']);

        $this->registry->get(UserInterface::class)
            ->willReturn($locator = $this->prophesize(ServiceLocator::class))
        ;
        $locator->get('20171128')->willReturn(new \stdClass());

        self::assertNotNull($this->resolver->resolve(UserInterface::class, $request->reveal()));
    }

    public function testHasShouldReturnTrueIfModelIsPresentInRegistry(): void
    {
        $this->registry->has(UserInterface::class)->willReturn(true);
        self::assertTrue($this->resolver->has(UserInterface::class));
    }

    public function testHasShouldReturnFalseIfModelIsNotPresentInRegistry(): void
    {
        $this->registry->has(UserInterface::class)->willReturn(false);
        self::assertFalse($this->resolver->has(UserInterface::class));
    }
}
