<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Proxy\Generator;

use Fazland\DtoManagementBundle\Annotation\Security;
use Fazland\DtoManagementBundle\Annotation\Transform;
use Fazland\DtoManagementBundle\Proxy\ExpressionLanguage;
use Fazland\DtoManagementBundle\Proxy\Generator\PropertyGenerator\ServiceLocatorHolder;
use Fazland\DtoManagementBundle\Proxy\ProxyInterface;
use ProxyManager\Generator\Util\UniqueIdentifierGenerator;
use ProxyManager\ProxyGenerator\Assertion\CanProxyAssertion;
use ProxyManager\ProxyGenerator\LazyLoadingValueHolder\PropertyGenerator\ValueHolderProperty;
use ProxyManager\ProxyGenerator\PropertyGenerator\PublicPropertiesMap;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;
use ProxyManager\ProxyGenerator\Util\Properties;
use Symfony\Component\DependencyInjection\ServiceSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;
use Symfony\Component\Security\Core\Security as SymfonySecurity;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator as ZendMethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Reflection\MethodReflection;

class AccessInterceptorGenerator implements ProxyGeneratorInterface
{
    /**
     * @var ExpressionLanguage
     */
    protected $expressionLanguage;

    public function __construct(?BaseExpressionLanguage $expressionLanguage = null)
    {
        $this->expressionLanguage = $expressionLanguage;

        if (null === $expressionLanguage && class_exists(BaseExpressionLanguage::class)) {
            $this->expressionLanguage = new ExpressionLanguage();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generate(\ReflectionClass $originalClass, ClassGenerator $classGenerator, array $options = []): void
    {
        CanProxyAssertion::assertClassCanBeProxied($originalClass);

        $publicProperties = new PublicPropertiesMap(Properties::fromReflectionClass($originalClass));
        $interfaces = [ProxyInterface::class, ServiceSubscriberInterface::class];

        if ($originalClass->isInterface()) {
            $interfaces[] = $originalClass->getName();
        } else {
            $classGenerator->setExtendedClass($originalClass->getName());
        }

        $classGenerator->setImplementedInterfaces($interfaces);
        $classGenerator->addPropertyFromGenerator($valueHolder = new ValueHolderProperty());
        $valueHolder->setDocBlock('@var \\'.$originalClass->getName().' Object containing the public properties');

        $classGenerator->addPropertyFromGenerator($publicProperties);

        $classGenerator->addPropertyFromGenerator($locatorHolder = new ServiceLocatorHolder());
        $classGenerator->addMethodFromGenerator(MethodGenerator\Constructor::generateMethod($originalClass, $valueHolder, $locatorHolder));

        foreach (ProxiedMethodsFilter::getProxiedMethods($originalClass) as $proxiedMethod) {
            $interceptors = $this->generateInterceptors($proxiedMethod, $options['method_interceptors'][$proxiedMethod->getName()] ?? [], $locatorHolder);
            if (0 === count($interceptors)) {
                continue;
            }

            if ($proxiedMethod->isFinal()) {
                throw new \InvalidArgumentException('Method "'.$proxiedMethod->getName().'" is marked as final and cannot be proxied.');
            }

            $classGenerator->addMethodFromGenerator($this->generateInterceptedMethod($proxiedMethod, $interceptors));
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
                if (! class_exists(BaseExpressionLanguage::class)) {
                    throw new \RuntimeException('Please install symfony/expression-language');
                }

                if (! class_exists(SymfonySecurity::class)) {
                    throw new \RuntimeException('Please install symfony/security');
                }

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

                if (count($forwardedParams) > 0) {
                    $usedParams = ' use ('.implode(', ', array_map(function (string $name) {
                        return '$'.$name;
                    }, $forwardedParams)).')';
                } else {
                    $usedParams = '';
                }

                if (Security::RETURN_NULL === $annotation->onInvalid) {
                    $onInvalid = 'return null;';
                } else {
                    $onInvalid = "throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException($message);";
                }

                return <<<PHP
\${$property} = function ()$usedParams: bool {
    \$auth_checker = \$this->{$locatorHolder->getName()}->get('security.authorization_checker');
    \$token = \$this->{$locatorHolder->getName()}->get('security.token_storage')->getToken();
    \$object = \$this;
    \$user = null !== \$token ? \$token->getUser() : null;

    return {$this->expressionLanguage->compile($annotation->expression, array_merge(['auth_checker', 'token', 'object', 'user'], $forwardedParams))};
};

if (! \$$property()) {
    $onInvalid
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

    private function generateInterceptedMethod(\ReflectionMethod $originalMethod, array $interceptors): ZendMethodGenerator
    {
        $method = ZendMethodGenerator::fromReflection(new MethodReflection($originalMethod->getDeclaringClass()->getName(), $originalMethod->getName()));
        if (PHP_VERSION_ID >= 70200) {
            foreach ($method->getParameters() as $parameter) {
                \Closure::bind(function () {
                    $this->type = null;
                }, $parameter, ParameterGenerator::class)();
            }
        }

        $forwardedParams = [];

        foreach ($originalMethod->getParameters() as $parameter) {
            $forwardedParams[] = ($parameter->isVariadic() ? '...' : '').'$'.$parameter->getName();
        }

        $forwardedParams = implode(', ', $forwardedParams);
        $interceptors = implode(";\n", $interceptors).';';
        $return = 'return ';

        $returnType = $originalMethod->getReturnType();
        if (null !== $returnType && 'void' === $returnType->getName()) {
            $return = '';
        }

        $body = "$interceptors\n{$return}parent::{$originalMethod->getName()}($forwardedParams);";

        $method->setDocBlock('{@inheritDoc}');
        $method->setBody($body);

        return $method;
    }
}
