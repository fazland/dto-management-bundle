<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Utils;

class ClassUtils
{
    /**
     * Checks if an interface exists
     *
     * @param string $classOrInterface
     * @param bool $autoload
     *
     * @return bool
     */
    public function interfaceExists(string $classOrInterface, bool $autoload = true): bool
    {
        return \interface_exists($classOrInterface, $autoload);
    }
}
