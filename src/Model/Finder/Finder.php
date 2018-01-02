<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Model\Finder;

use Symfony\Component\Config\Resource\GlobResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Finder
{
    public static function findClasses(ContainerBuilder $container, string $namespace, string $path): array
    {
        $path = realpath($path);
        $resource = new GlobResource($path, '*', true);
        $prefixLen = strlen($path);
        $classes = [];

        foreach ($resource as $path => $info) {
            if (! preg_match('/\\.php$/', $path, $m) || ! $info->isReadable()) {
                continue;
            }

            $class = $namespace.ltrim(str_replace('/', '\\', substr($path, $prefixLen, -strlen($m[0]))), '\\');
            if (! preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+(?:\\\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+)*+$/', $class)) {
                die;
                continue;
            }

            if (! $r = $container->getReflectionClass($class)) {
                continue;
            }

            if ($r->isInstantiable() || $r->isInterface()) {
                $classes[] = $class;
            }
        }

        $container->addResource($resource);

        return $classes;
    }
}
