<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\InterfaceResolver;

use Fazland\DtoManagementBundle\Finder\ServiceLocatorRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class Resolver implements ResolverInterface
{
    /**
     * @var ServiceLocatorRegistry
     */
    private $registry;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(ServiceLocatorRegistry $registry, ?RequestStack $requestStack = null)
    {
        $this->registry = $registry;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $interface, ?Request $request = null)
    {
        if (null === $request && null !== $this->requestStack) {
            $request = $this->requestStack->getCurrentRequest();
        }

        $version = (null !== $request ? $request->attributes->get('_version') : null) ?? (new \DateTime())->format('Ymd');
        $locator = $this->registry->get($interface);

        return $locator->get($version);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $interface): bool
    {
        return $this->registry->has($interface);
    }
}
