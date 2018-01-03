<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Finder;

class ServiceLocatorRegistry
{
    /**
     * @var array
     */
    private $locators;

    public function __construct(array $locators)
    {
        $this->locators = $locators;
    }

    public function get(string $interface): ServiceLocator
    {
        if (! isset($this->locators[$interface])) {
            throw new \RuntimeException('Cannot find service locator for "'.$interface.'"');
        }

        return $this->locators[$interface];
    }

    public function has(string $interface): bool
    {
        return isset($this->locators[$interface]);
    }
}
