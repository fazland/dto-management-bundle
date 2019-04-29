<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Fazland\DtoManagementBundle\Annotation\Security;
use Fazland\DtoManagementBundle\Annotation\Transform;
use Fazland\DtoManagementBundle\Finder\ServiceLocatorRegistry;
use Fazland\DtoManagementBundle\Proxy\Factory\AccessInterceptorFactory;
use Kcs\ClassFinder\Finder\RecursiveFinder;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AddInterceptorsPass implements CompilerPassInterface
{
    /**
     * @var AccessInterceptorFactory
     */
    private $proxyFactory;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    public function process(ContainerBuilder $container): void
    {
        $cacheDir = $container->getParameterBag()->resolveValue(
            $container->getParameter('fazland.dto-management.proxy_cache_dir')
        );

        if (! @\mkdir($cacheDir, 0777, true) && ! \is_dir($cacheDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $cacheDir));
        }

        $this->proxyFactory = $container->get('fazland.dto-management.proxy_factory');
        AnnotationRegistry::registerUniqueLoader('class_exists');

        $this->annotationReader = $container->get('annotations.reader');

        $definition = $container->findDefinition(ServiceLocatorRegistry::class);
        $interfaces = $definition->getArgument(0);

        foreach ($interfaces as $interface => $serviceLocator) {
            $this->processLocator($container, $serviceLocator);
        }

        $this->generateClassMap($cacheDir, $container->getParameter('kernel.cache_dir').'/dto-proxies-map.php');
    }

    private function processLocator(ContainerBuilder $container, ServiceClosureArgument $argument): void
    {
        $locator = $container->getDefinition((string) $argument->getValues()[0]);
        /** @var Reference[] $versions */
        $versions = $locator->getArgument(0);

        foreach ($versions as $version) {
            $definition = $container->findDefinition((string) $version->getValues()[0]);
            $class = $definition->getClass();
            $reflector = $container->getReflectionClass($class);

            $methodInterceptors = [];
            $propertyInterceptors = [];
            $subscribedServices = [];

            foreach ($reflector->getMethods() as $method) {
                $params = $method->getParameters();

                /** @var Transform $annotation */
                $annotation = $this->annotationReader->getMethodAnnotation($method, Transform::class);

                if (null !== $annotation) {
                    if (1 !== \count($params)) {
                        throw new \LogicException('Transformations can be applied to methods with 1 parameter only. '.$method->getName().' has '.$method->getNumberOfParameters());
                    }

                    $subscribedServices[$annotation->service] = true;
                    $methodInterceptors[$method->getName()][] = [
                        'annotation' => $annotation,
                        'parameter' => $params[0]->getName(),
                    ];
                }

                /** @var Security $annot */
                $annotation = $this->annotationReader->getMethodAnnotation($method, Security::class);

                if (null !== $annotation) {
                    $subscribedServices['security.authorization_checker'] = AuthorizationCheckerInterface::class;
                    $subscribedServices['security.token_storage'] = TokenStorageInterface::class;
                    $methodInterceptors[$method->getName()][] = [
                        'annotation' => $annotation,
                        'parameter' => '',
                    ];
                }
            }

            foreach ($reflector->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                /** @var Transform $annot */
                $annotation = $this->annotationReader->getPropertyAnnotation($property, Transform::class);

                if (null !== $annotation) {
                    $subscribedServices[$annotation->service] = true;
                    $propertyInterceptors[$property->getName()][] = [
                        'annotation' => $annotation,
                        'parameter' => 'value',
                    ];
                }

                /** @var Security $annot */
                $annotation = $this->annotationReader->getPropertyAnnotation($property, Security::class);

                if (null !== $annotation) {
                    $subscribedServices['security.authorization_checker'] = AuthorizationCheckerInterface::class;
                    $subscribedServices['security.token_storage'] = TokenStorageInterface::class;
                    $propertyInterceptors[$property->getName()][] = [
                        'annotation' => $annotation,
                        'parameter' => '',
                    ];
                }
            }

            foreach ($subscribedServices as $name => &$service) {
                if (\is_bool($service)) {
                    $service = $container->findDefinition($name)->getClass();
                }
            }

            unset($service);

            if ($methodInterceptors || $propertyInterceptors) {
                $proxyClass = $this->proxyFactory->generateProxy($class, [
                    'method_interceptors' => $methodInterceptors,
                    'property_interceptors' => $propertyInterceptors,
                    'services' => $subscribedServices,
                ]);

                $definition->setClass($proxyClass);
                foreach ($subscribedServices as $id => $type) {
                    $definition->addTag('container.service_subscriber', [
                        'key' => $id,
                        'id' => $id,
                    ]);
                }
            }
        }
    }

    private function generateClassMap(string $cacheDir, string $outFile): void
    {
        $map = [];
        $finder = new RecursiveFinder($cacheDir);

        foreach ($finder as $class => $reflector) {
            $map[$class] = $reflector->getFileName();
        }

        \file_put_contents($outFile, '<?php return '.\var_export($map, true).';');
    }
}
