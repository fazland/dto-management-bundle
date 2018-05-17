<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle;

use Fazland\DtoManagementBundle\InterfaceResolver\ResolverInterface;
use Fazland\DtoManagementBundle\Tests\Fixtures\Proxy\AppKernel;
use Fazland\DtoManagementBundle\Tests\Fixtures\Proxy\Model\Interfaces\ExcludedInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @group functional
 */
class ProxyTest extends WebTestCase
{
    public function testShouldReturn401IfNotLoggedIn(): void
    {
        $client = $this->createClient();
        $client->request('GET', '/');

        $response = $client->getResponse();
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testShouldThrowAccessDeniedExceptionIfRoleDoesNotMatch()
    {
        $client = $this->createClient();
        $client->request('GET', '/protected', [], [], [
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW' => 'user',
        ]);

        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testShouldExecuteOperationsIfRolesAreCorrect()
    {
        $client = $this->createClient();
        $client->request('GET', '/protected', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('"CIAO"', $response->getContent());
    }

    public function testShouldReturnNullIfOnInvalidFlagsIsSet()
    {
        $client = $this->createClient();
        $client->request('GET', '/unavailable', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('null', $response->getContent());
    }

    public function testExcludedInterfacesShouldNotBeRegistered()
    {
        $client = $this->createClient();
        $client->getKernel()->boot();

        $container = $client->getContainer();
        $this->assertFalse($container->get(ResolverInterface::class)->has(ExcludedInterface::class));
    }

    protected static function createKernel(array $options = [])
    {
        return new AppKernel('test', true);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/Fixtures/Proxy/cache');
        $fs->remove(__DIR__.'/Fixtures/Proxy/logs');
    }
}
