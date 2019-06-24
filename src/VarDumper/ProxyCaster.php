<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\VarDumper;

use Fazland\DtoManagementBundle\Proxy\ProxyInterface;
use Symfony\Component\VarDumper\Cloner\Stub;

final class ProxyCaster
{
    public static function castDtoProxy(ProxyInterface $proxy, array $a, Stub $stub, bool $isNested): array
    {
        $original = $a;
        $prefix = "\0".\get_class($proxy)."\0";
        $valueHolder = null;

        foreach ($a as $key => $value) {
            if (0 === \strpos($key, $prefix.'valueHolder')) {
                $valueHolder = $value;
                unset($a[$key]);
            }

            if (0 === \strpos($key, $prefix.'serviceLocator')) {
                unset($a[$key]);
            }
        }

        if (null === $valueHolder) {
            return $original;
        }

        $a += (array) $valueHolder;
        $stub->class = \get_parent_class($proxy).' (proxy)';

        return $a;
    }
}
