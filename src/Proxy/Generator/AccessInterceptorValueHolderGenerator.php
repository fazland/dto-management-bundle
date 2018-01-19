<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Proxy\Generator;

use Fazland\DtoManagementBundle\Annotation\Security;
use Fazland\DtoManagementBundle\Annotation\Transform;
use Fazland\DtoManagementBundle\Proxy\ExpressionLanguage;
use Fazland\DtoManagementBundle\Proxy\Generator\PropertyGenerator\ServiceLocatorHolder;
use Fazland\DtoManagementBundle\Proxy\Generator\PropertyGenerator\ServiceLocatorHolderSetter;
use Fazland\DtoManagementBundle\Proxy\ProxyInterface;
use ProxyManager\Generator\Util\ClassGeneratorUtils;
use ProxyManager\Generator\Util\UniqueIdentifierGenerator;
use ProxyManager\ProxyGenerator\Assertion\CanProxyAssertion;
use ProxyManager\ProxyGenerator\LazyLoadingValueHolder\PropertyGenerator\ValueHolderProperty;
use ProxyManager\ProxyGenerator\PropertyGenerator\PublicPropertiesMap;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;
use ProxyManager\ProxyGenerator\Util\Properties;
use ProxyManager\ProxyGenerator\Util\ProxiedMethodsFilter;
use ProxyManager\ProxyGenerator\ValueHolder\MethodGenerator\Constructor;
use ProxyManager\ProxyGenerator\ValueHolder\MethodGenerator\GetWrappedValueHolderValue;
use Symfony\Component\DependencyInjection\ServiceSubscriberInterface;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Reflection\MethodReflection;
use Zend\Code\Generator\MethodGenerator as ZendMethodGenerator;

class AccessInterceptorValueHolderGenerator implements ProxyGeneratorInterface
{
    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * {@inheritDoc}
     */
    public function generate(\ReflectionClass $originalClass, ClassGenerator $classGenerator, array $options = [])
    {
        CanProxyAssertion::assertClassCanBeProxied($originalClass);

        $publicProperties = new PublicPropertiesMap(Properties::fromReflectionClass($originalClass));
        $interfaces       = [ProxyInterface::class, ServiceSubscriberInterface::class];

        if ($originalClass->isInterface()) {
            $interfaces[] = $originalClass->getName();
        } else {
            $classGenerator->setExtendedClass($originalClass->getName());
        }

        $classGenerator->setImplementedInterfaces($interfaces);
        $classGenerator->addPropertyFromGenerator($valueHolder = new ValueHolderProperty());
        $valueHolder->setDocBlock('@var \\'.$originalClass->getName().' The wrapped value');
        $classGenerator->addPropertyFromGenerator($publicProperties);

        $classGenerator->addPropertyFromGenerator($locatorHolder = new ServiceLocatorHolder());
        $classGenerator->addMethodFromGenerator(new ServiceLocatorHolderSetter($locatorHolder));

        $classGenerator->addMethodFromGenerator(Constructor::generateMethod($originalClass, $valueHolder));
        $classGenerator->addMethodFromGenerator(new GetWrappedValueHolderValue($valueHolder));

        foreach (ProxiedMethodsFilter::getProxiedMethods($originalClass) as $proxiedMethod) {
            $interceptors = $this->generateInterceptors($proxiedMethod, $options['method_interceptors'][$proxiedMethod->getName()] ?? [], $locatorHolder);
            if (0 === count($interceptors)) {
                continue;
            }

            ClassGeneratorUtils::addMethodIfNotFinal($originalClass, $classGenerator, $this->generateInterceptedMethod($proxiedMethod, $valueHolder, $interceptors));
        }

        $propertyInterceptors = $options['property_interceptors'];
        foreach ($propertyInterceptors as $name => $interceptors) {
            $propertyInterceptors[$name] = $this->generateInterceptors(null, $interceptors, $locatorHolder);
        }

        $classGenerator->addMethodFromGenerator(new MethodGenerator\MagicSet($originalClass, $valueHolder, $publicProperties, $propertyInterceptors));
        $classGenerator->addMethodFromGenerator(new MethodGenerator\MagicGet($originalClass, $valueHolder, $publicProperties));
        $classGenerator->addMethodFromGenerator(new MethodGenerator\MagicIsset($originalClass, $valueHolder, $publicProperties));
        $classGenerator->addMethodFromGenerator(new MethodGenerator\GetSubscribedServices($originalClass, $options['services']));
    }

    private function generateInterceptor(?\ReflectionMethod $originalMethod, $annotation, ServiceLocatorHolder $locatorHolder): string
    {
        switch (true) {
            case $annotation instanceof Transform:
                $param = null === $originalMethod ? 'value' : $originalMethod->getParameters()[0]->getName();
                return "\${$param} = \$this->{$locatorHolder->getName()}->get('$annotation->service')->reverseTransform(\${$param})";

            case $annotation instanceof Security:
                $property = UniqueIdentifierGenerator::getIdentifier('check');
                $message = var_export($annotation->message ?: 'Expression "'.$annotation->expression.'" denied access.', true);

                $forwardedParams = [];
                if (null !== $originalMethod) {
                    foreach ($originalMethod->getParameters() as $parameter) {
                        $forwardedParams[] = $parameter->getName();
                    }
                } else {
                    $forwardedParams[] = 'value';
                }

                $usedParams = implode(', ', array_map(function (string $name) {
                    return '$'.$name;
                }, $forwardedParams));

                return <<<PHP
\${$property} = function () use ($usedParams): bool {
    \$auth_checker = \$this->{$locatorHolder->getName()}->get('security.authorization_checker');
    \$token = \$this->{$locatorHolder->getName()}->get('security.token_storage')->getToken();
    \$object = \$this;
    \$user = null !== \$token ? \$token->getUser() : null;

    return {$this->expressionLanguage->compile($annotation->expression, array_merge(['auth_checker', 'token', 'object', 'user'], $forwardedParams))};
};

if (! \$$property()) {
    throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException($message);
}
PHP;

        }
    }

    private function generateInterceptors(?\ReflectionMethod $originalMethod, array $interceptors, ServiceLocatorHolder $locatorHolder): array
    {
        return array_map(function (array $interceptor) use ($originalMethod, $locatorHolder) {
            return $this->generateInterceptor($originalMethod, $interceptor['annotation'], $locatorHolder);
        }, $interceptors);
    }

    private function generateInterceptedMethod(\ReflectionMethod $originalMethod, ValueHolderProperty $valueHolder, array $interceptors): ZendMethodGenerator
    {
        $method = ZendMethodGenerator::fromReflection(new MethodReflection($originalMethod->getDeclaringClass()->getName(), $originalMethod->getName()));
        $forwardedParams = [];

        foreach ($originalMethod->getParameters() as $parameter) {
            $forwardedParams[] = ($parameter->isVariadic() ? '...' : '') . '$' . $parameter->getName();
        }

        $forwardedParams = implode(', ', $forwardedParams);
        $interceptors = implode(";\n", $interceptors) . ';';
        $return = 'return ';

        $returnType = $originalMethod->getReturnType();
        if (null !== $returnType && $returnType->getName() === 'void') {
            $return = '';
        }

        $body = "$interceptors\n$return\$this->{$valueHolder->getName()}->{$originalMethod->getName()}($forwardedParams);";

        $method->setDocBlock('{@inheritDoc}');
        $method->setBody($body);

        return $method;
    }
}
