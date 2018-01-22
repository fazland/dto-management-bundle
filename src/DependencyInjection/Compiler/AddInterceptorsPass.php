<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Fazland\DtoManagementBundle\Annotation\Security;
use Fazland\DtoManagementBundle\Annotation\Transform;
use Fazland\DtoManagementBundle\Finder\ServiceLocatorRegistry;
use Fazland\DtoManagementBundle\Proxy\Factory\AccessInterceptorFactory;
use Kcs\ClassFinder\Finder\RecursiveFinder;
use ProxyManager\Configuration;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AddInterceptorsPass implements CompilerPassInterface
{
    /**
     * @var \Fazland\DtoManagementBundle\Proxy\Factory\AccessInterceptorFactory
     */
    private $proxyFactory;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    public function process(ContainerBuilder $container)
    {
        $cacheDir = $container->getParameterBag()->resolveValue(
            $container->getParameter('fazland.dto-management.proxy_cache_dir')
        );
        @mkdir($cacheDir, 0777, true);

        $this->proxyFactory = $container->get('fazland.dto-management.proxy_factory');
        AnnotationRegistry::registerUniqueLoader('class_exists');

        $this->annotationReader = new AnnotationReader();

        $definition = $container->findDefinition(ServiceLocatorRegistry::class);
        $interfaces = $definition->getArgument(0);

        foreach ($interfaces as $interface => $serviceLocator) {
            $this->processLocator($container, $serviceLocator);
        }

        $this->generateClassMap($cacheDir, $container->getParameter('kernel.cache_dir').'/dto-proxies-map.php');
    }

    private function processLocator(ContainerBuilder $container, Definition $locator): void
    {
        /** @var Reference[] $versions */
        $versions = $locator->getArgument(0);

        foreach ($versions as $version) {
            $definition = $container->findDefinition((string) $version);
            $class = $definition->getClass();
            $reflector = $container->getReflectionClass($class);

            $methodInterceptors = [];
            $propertyInterceptors = [];
            $subscribedServices = [];

            foreach ($reflector->getMethods() as $method) {
                $params = $method->getParameters();

                /** @var Transform $annot */
                $annot = $this->annotationReader->getMethodAnnotation($method, Transform::class);

                if (null !== $annot) {
                    if (count($params) !== 1) {
                        throw new \LogicException('Transformations can be applied to methods with 1 parameter only. '.$method->getName().' has '.$method->getNumberOfParameters());
                    }

                    $subscribedServices[$annot->service] = true;
                    $methodInterceptors[$method->getName()][] = [
                        'annotation' => $annot,
                        'parameter' => $params[0]->getName(),
                    ];
                }

                /** @var Security $annot */
                $annot = $this->annotationReader->getMethodAnnotation($method, Security::class);

                if (null !== $annot) {
                    $subscribedServices['security.authorization_checker'] = AuthorizationCheckerInterface::class;
                    $subscribedServices['security.token_storage'] = TokenStorageInterface::class;
                    $methodInterceptors[$method->getName()][] = [
                        'annotation' => $annot,
                        'parameter' => '',
                    ];
                }
            }

            foreach ($reflector->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                /** @var Transform $annot */
                $annot = $this->annotationReader->getPropertyAnnotation($property, Transform::class);

                if (null !== $annot) {
                    $subscribedServices[$annot->service] = true;
                    $propertyInterceptors[$property->getName()][] = [
                        'annotation' => $annot,
                        'parameter' => 'value',
                    ];
                }

                /** @var Security $annot */
                $annot = $this->annotationReader->getPropertyAnnotation($property, Security::class);

                if (null !== $annot) {
                    $subscribedServices['security.authorization_checker'] = AuthorizationCheckerInterface::class;
                    $subscribedServices['security.token_storage'] = TokenStorageInterface::class;
                    $propertyInterceptors[$property->getName()][] = [
                        'annotation' => $annot,
                        'parameter' => '',
                    ];
                }
            }

            foreach ($subscribedServices as $name => &$service) {
                if (is_bool($service)) {
                    $service = $container->findDefinition($name)->getClass();
                }
            }

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

        /**
         * @var string $class
         * @var \ReflectionClass $reflector
         */
        foreach ($finder as $class => $reflector) {
            $map[$class] = $reflector->getFileName();
        }

        file_put_contents($outFile, '<?php return '.var_export($map, true).';');
    }
}
