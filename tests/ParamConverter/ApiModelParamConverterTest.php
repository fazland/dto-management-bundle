<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\ParamConverter;

use Fazland\DtoManagementBundle\Finder\ServiceLocator;
use Fazland\DtoManagementBundle\Finder\ServiceLocatorRegistry;
use Fazland\DtoManagementBundle\ParamConverter\ApiModelParamConverter;
use Fazland\DtoManagementBundle\Tests\Fixtures\ModelConverter\AppKernel;
use Fazland\DtoManagementBundle\Tests\Fixtures\ModelConverter\Model\Interfaces\UserInterface;
use Fazland\DtoManagementBundle\Tests\Fixtures\ModelConverter\Model\v2017\v20171128\User as User20171128;
use Fazland\DtoManagementBundle\Tests\Fixtures\ModelConverter\Model\v2017\v20171215\User as User20171215;
use Prophecy\Prophecy\ObjectProphecy;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class ApiModelParamConverterTest extends WebTestCase
{
    /**
     * @var ServiceLocatorRegistry|ObjectProphecy
     */
    private $serviceLocatorRegistry;

    /**
     * @var ApiModelParamConverter
     */
    private $converter;

    protected function setUp()
    {
        $this->serviceLocatorRegistry = $this->prophesize(ServiceLocatorRegistry::class);
        $this->converter = new ApiModelParamConverter($this->serviceLocatorRegistry->reveal());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testApplyShouldThrowHttpNotFoundExceptionIfServiceCannotBeFound()
    {
        $request = $this->prophesize(Request::class);

        $converter = new ParamConverter([
            'name' => 'user',
            'class' => UserInterface::class,
        ]);

        $request->attributes = new ParameterBag(['_version' => '20171128']);
        $this->serviceLocatorRegistry->get(UserInterface::class)
            ->willReturn($locator = $this->prophesize(ServiceLocator::class));
        $locator->get('20171128')->willThrow(new ServiceNotFoundException('20171128'));

        $this->assertFalse($this->converter->apply($request->reveal(), $converter));
    }

    public function testApplyShouldReturnFalseOnError()
    {
        $request = $this->prophesize(Request::class);

        $converter = new ParamConverter([
            'name' => 'user',
            'class' => UserInterface::class,
        ]);

        $request->attributes = new ParameterBag(['_version' => '20171128']);
        $this->serviceLocatorRegistry->get(UserInterface::class)
            ->willReturn($locator = $this->prophesize(ServiceLocator::class));
        $locator->get('20171128')->willThrow(new ServiceCircularReferenceException('20171128', []));

        $this->assertFalse($this->converter->apply($request->reveal(), $converter));
    }

    public function testSupportsShouldReturnTrueIfModelIsPresentInRegistry()
    {
        $converter = new ParamConverter([
            'name' => 'user',
            'class' => UserInterface::class,
        ]);

        $this->serviceLocatorRegistry->has(UserInterface::class)->willReturn(true);
        $this->assertTrue($this->converter->supports($converter));
    }

    public function testSupportsShouldReturnFalseIfModelIsNotPresentInRegistry()
    {
        $converter = new ParamConverter([
            'name' => 'user',
            'class' => UserInterface::class,
        ]);

        $this->serviceLocatorRegistry->has(UserInterface::class)->willReturn(false);
        $this->assertFalse($this->converter->supports($converter));
    }

    /**
     * @group functional
     */
    public function testApplyShouldFillRequestAttributes()
    {
        $client = static::createClient();

        $client->request('GET', '/', [], [], ['HTTP_X_VERSION' => '20171201']);
        $this->assertEquals(User20171128::class, $client->getResponse()->getContent());

        $client->request('GET', '/', [], [], ['HTTP_X_VERSION' => '20171226']);
        $this->assertEquals(User20171215::class, $client->getResponse()->getContent());
    }

    protected static function createKernel(array $options = [])
    {
        return new AppKernel('test', true);
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/../Fixtures/ModelConverter/cache');
        $fs->remove(__DIR__.'/../Fixtures/ModelConverter/logs');
    }
}
