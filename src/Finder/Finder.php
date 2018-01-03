<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Finder;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Resource\GlobResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Finder
{
    public static function findClasses(ContainerBuilder $container, string $namespace, string $path): array
    {
        $realpath = realpath($path);
        if (false === $realpath) {
            throw new InvalidConfigurationException($path.' does not exist');
        }

        $resource = new GlobResource($realpath, '*', true);
        $prefixLen = strlen($realpath);
        $classes = [];

        foreach ($resource as $realpath => $info) {
            if (! preg_match('/\\.php$/', $realpath, $m) || ! $info->isReadable()) {
                continue;
            }

            $class = $namespace.ltrim(str_replace('/', '\\', substr($realpath, $prefixLen, -strlen($m[0]))), '\\');
            if (! preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+(?:\\\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+)*+$/', $class)) {
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
