<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\VarDumper;

use Fazland\DtoManagementBundle\Annotation\Security;
use Fazland\DtoManagementBundle\Proxy\Factory\AccessInterceptorFactory;
use Fazland\DtoManagementBundle\Proxy\ProxyInterface;
use Fazland\DtoManagementBundle\Tests\Fixtures\Proxy\Model\v2017\v20171215\User;
use Fazland\DtoManagementBundle\VarDumper\ProxyCaster;
use PHPUnit\Framework\TestCase;
use ProxyManager\Configuration;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use Psr\Container\ContainerInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

class ProxyCasterTest extends TestCase
{
    use VarDumperTestTrait;

    protected function getDump($data, $key = null, $filter = 0)
    {
        $flags = \getenv('DUMP_LIGHT_ARRAY') ? CliDumper::DUMP_LIGHT_ARRAY : 0;
        $flags |= \getenv('DUMP_STRING_LENGTH') ? CliDumper::DUMP_STRING_LENGTH : 0;
        $flags |= \getenv('DUMP_COMMA_SEPARATOR') ? CliDumper::DUMP_COMMA_SEPARATOR : 0;

        $cloner = new VarCloner();
        $cloner->setMaxItems(-1);
        $cloner->addCasters([ProxyInterface::class => ProxyCaster::class.'::castDtoProxy']);

        $dumper = new CliDumper(null, null, $flags);
        $dumper->setColors(false);
        $data = $cloner->cloneVar($data, $filter)->withRefHandles(false);
        if (null !== $key && null === $data = $data->seek($key)) {
            return;
        }

        return \rtrim($dumper->dump($data, true));
    }

    public function testShouldCastDtoProxies(): void
    {
        $configuration = new Configuration();
        $configuration->setGeneratorStrategy(new EvaluatingGeneratorStrategy());

        $factory = new AccessInterceptorFactory($configuration);
        $annotation = new Security();
        $annotation->expression = 'is_granted(\'ROLE_ADMIN\')';

        $className = $factory->generateProxy(User::class, [
            'property_interceptors' => [
                'foobar' => [
                    [
                        'annotation' => $annotation,
                        'parameter' => '',
                    ],
                ],
            ],
            'services' => [],
        ]);

        $container = $this->prophesize(ContainerInterface::class);
        $obj = new $className($container->reveal());

        $this->assertDumpEquals(<<<DUMP
Fazland\\DtoManagementBundle\\Tests\\Fixtures\\Proxy\\Model\\v2017\\v20171215\\User (proxy) {
  +barPublic: "pubb"
  +barBar: "test"
  +foobar: "ciao"
}
DUMP
        , $obj);
    }
}
