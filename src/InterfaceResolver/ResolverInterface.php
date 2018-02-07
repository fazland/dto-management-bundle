<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\InterfaceResolver;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves model interfaces to service instances.
 */
interface ResolverInterface
{
    /**
     * Resolve the given interface and return the corresponding
     * service from the service container.
     *
     * @param string $interface
     * @param Request $request
     *
     * @return mixed
     */
    public function resolve(string $interface, ?Request $request = null);

    /**
     * Checks whether the given interface could be resolved.
     *
     * @param string $interface
     *
     * @return bool
     */
    public function has(string $interface): bool;
}
