<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\Finder;

use Fazland\DtoManagementBundle\Finder\ServiceLocator;
use Fazland\DtoManagementBundle\Tests\Fixtures\DependencyInjection\Model\v2017\v20171215\User;
use PHPUnit\Framework\TestCase;

class ServiceLocatorTest extends TestCase
{
    /**
     * @var ServiceLocator
     */
    private $locator;

    protected function setUp()
    {
        $this->locator = new ServiceLocator([
            20171215 => function () {
                return new User();
            },
            20171118 => function () {
                return 'foo';
            },
            20180213 => function () {
                return 'bar';
            },
        ]);
    }

    public function testLocatorShouldBeInvokable()
    {
        self::assertTrue(\is_callable($this->locator));
    }

    public function testLocatorInvokingWithNonExistentServiceReturnsNull()
    {
        $locator = $this->locator;
        self::assertNull($locator(20160730));
    }

    public function testLocatorGetShouldReturnTheClosestLesserImplementation()
    {
        self::assertInstanceOf(User::class, $this->locator->get(20180101));
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function testLocatorGetShouldThrowIfNoImplementationIsAvailableForDate()
    {
        $this->locator->get(20150101);
    }

    public function testLocatorHasShouldWork()
    {
        self::assertFalse($this->locator->has(20150101));
        self::assertTrue($this->locator->has(20171118));
    }
}
