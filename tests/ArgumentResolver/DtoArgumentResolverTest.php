<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\ArgumentResolver;

use Fazland\DtoManagementBundle\ArgumentResolver\DtoArgumentResolver;
use Fazland\DtoManagementBundle\Finder\ServiceLocatorRegistry;
use Fazland\DtoManagementBundle\InterfaceResolver\ResolverInterface;
use Fazland\DtoManagementBundle\Tests\Fixtures\ModelConverter\AppKernel;
use Fazland\DtoManagementBundle\Tests\Fixtures\ModelConverter\Model\Interfaces\UserInterface;
use Fazland\DtoManagementBundle\Tests\Fixtures\ModelConverter\Model\v2017\v20171128\User as User20171128;
use Fazland\DtoManagementBundle\Tests\Fixtures\ModelConverter\Model\v2017\v20171215\User as User20171215;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\KernelInterface;

class DtoArgumentResolverTest extends WebTestCase
{
    /**
     * @var ServiceLocatorRegistry|ObjectProphecy
     */
    private $serviceLocatorRegistry;

    /**
     * @var ResolverInterface|ObjectProphecy
     */
    private $resolver;

    /**
     * @var DtoArgumentResolver
     */
    private $converter;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resolver = $this->prophesize(ResolverInterface::class);
        $this->serviceLocatorRegistry = $this->prophesize(ServiceLocatorRegistry::class);
        $this->converter = new DtoArgumentResolver($this->resolver->reveal());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testApplyShouldThrowHttpNotFoundExceptionIfServiceCannotBeFound(): void
    {
        $request = $this->prophesize(Request::class);

        $argument = new ArgumentMetadata('user', UserInterface::class, false, false, null);
        $request->attributes = new ParameterBag(['_version' => '20171128']);

        $this->resolver->resolve(UserInterface::class, $request->reveal())
            ->willThrow(new ServiceNotFoundException('20171128'));
        foreach ($this->converter->resolve($request->reveal(), $argument) as $argument) {
        }
    }

    public function testSupportsShouldReturnTrueIfModelIsPresentInRegistry(): void
    {
        $request = $this->prophesize(Request::class);
        $argument = new ArgumentMetadata('user', UserInterface::class, false, false, null);

        $this->resolver->has(UserInterface::class)->willReturn(true);
        self::assertTrue($this->converter->supports($request->reveal(), $argument));
    }

    public function testSupportsShouldReturnFalseIfModelIsNotPresentInRegistry(): void
    {
        $request = $this->prophesize(Request::class);
        $argument = new ArgumentMetadata('user', UserInterface::class, false, false, null);

        $this->resolver->has(UserInterface::class)->willReturn(false);
        self::assertFalse($this->converter->supports($request->reveal(), $argument));
    }

    /**
     * @group functional
     */
    public function testApplyShouldFillRequestAttributes(): void
    {
        $client = static::createClient();

        $client->request('GET', '/', [], [], ['HTTP_X_VERSION' => '20171201']);
        self::assertEquals(User20171128::class, $client->getResponse()->getContent());

        $client->request('GET', '/', [], [], ['HTTP_X_VERSION' => '20171226']);
        self::assertEquals(User20171215::class, $client->getResponse()->getContent());
    }

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', true);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        self::ensureKernelShutdown();

        $fs = new Filesystem();
        $fs->remove(__DIR__.'/../Fixtures/ModelConverter/cache');
        $fs->remove(__DIR__.'/../Fixtures/ModelConverter/logs');
    }
}
