<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle;

use Fazland\DtoManagementBundle\Tests\Fixtures\DependencyInjection\AppKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class DtoManagementBundleTest extends TestCase
{
    /**
     * @group functional
     */
    public function testApplyShouldFillRequestAttributes(): void
    {
        $kernel = new AppKernel('test', true);
        $kernel->boot();

        self::assertTrue(true);

        $kernel->shutdown();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/Fixtures/DependencyInjection/cache');
        $fs->remove(__DIR__.'/Fixtures/DependencyInjection/logs');
    }
}
