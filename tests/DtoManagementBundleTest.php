<?php declare(strict_types=1);

namespace DependencyInjection;

use Fazland\DtoManagementBundle\Tests\Fixtures\DependencyInjection\AppKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class DtoManagementBundleTest extends TestCase
{
    /**
     * @group functional
     */
    public function testApplyShouldFillRequestAttributes()
    {
        $kernel = new AppKernel('test', true);
        $kernel->boot();

        $this->assertTrue(true);
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/Fixtures/DependencyInjection/cache');
        $fs->remove(__DIR__.'/Fixtures/DependencyInjection/logs');
    }
}
