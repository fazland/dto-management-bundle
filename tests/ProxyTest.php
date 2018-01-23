<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle;

use Fazland\DtoManagementBundle\Tests\Fixtures\Proxy\AppKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

class ProxyTest extends WebTestCase
{
    /**
     * @group functional
     */
    public function testShouldReturn401IfNotLoggedIn(): void
    {
        $client = $this->createClient();
        $client->request('GET', '/');

        $response = $client->getResponse();
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testShouldThrowAccessDeniedExceptionIfRoleDoesNotMatch()
    {
        $client = $this->createClient();
        $client->request('GET', '/protected', [], [], [
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW' => 'user',
        ]);
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

    protected static function createKernel(array $options = array())
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
