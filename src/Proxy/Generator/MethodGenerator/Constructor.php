<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Proxy\Generator\MethodGenerator;

use ProxyManager\Generator\MethodGenerator;
use ProxyManager\ProxyGenerator\Util\Properties;
use ProxyManager\ProxyGenerator\Util\UnsetPropertiesGenerator;
use Psr\Container\ContainerInterface;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Reflection\MethodReflection;

class Constructor extends MethodGenerator
{
    /**
     * Constructor
     *
     * @param \ReflectionClass $originalClass
     * @param PropertyGenerator $valueHolder
     *
     * @param PropertyGenerator $locator
     * @return self
     *
     */
    public static function generateMethod(\ReflectionClass $originalClass, PropertyGenerator $valueHolder, PropertyGenerator $locator) : self
    {
        $originalConstructor = self::getConstructor($originalClass);

        $constructor = $originalConstructor
            ? self::fromReflection($originalConstructor)
            : new self('__construct');

        $constructor->setParameter(new ParameterGenerator($locator->getName(), ContainerInterface::class));

        $constructor->setDocBlock('{@inheritDoc}');
        $constructor->setBody(
            '$this->' . $valueHolder->getName() . ' = new \stdClass();' . "\n"
            . '$this->'.$locator->getName().' = $'.$locator->getName().';'."\n"
            . UnsetPropertiesGenerator::generateSnippet(Properties::fromReflectionClass($originalClass), 'this')
            . self::generateOriginalConstructorCall($originalClass, $valueHolder)
        );

        return $constructor;
    }

    private static function generateOriginalConstructorCall(\ReflectionClass $class, PropertyGenerator $valueHolder) : string
    {
        $originalConstructor = self::getConstructor($class);
        if (null === $originalConstructor) {
            return '';
        }

        $constructor = self::fromReflection($originalConstructor);

        return "\n\n"
            . 'parent::' . $constructor->getName() . '('
            . implode(
                ', ',
                array_map(
                    function (ParameterGenerator $parameter) : string {
                        return ($parameter->getVariadic() ? '...' : '') . '$' . $parameter->getName();
                    },
                    $constructor->getParameters()
                )
            )
            . ');';
    }

    /**
     * Retrieves the constructor.
     *
     * @param \ReflectionClass $class
     *
     * @return MethodReflection|null
     */
    private static function getConstructor(\ReflectionClass $class): ?MethodReflection
    {
        $constructors = array_map(
            function (\ReflectionMethod $method) : MethodReflection {
                return new MethodReflection(
                    $method->getDeclaringClass()->getName(),
                    $method->getName()
                );
            },
            array_filter(
                $class->getMethods(),
                function (\ReflectionMethod $method) : bool {
                    return $method->isConstructor();
                }
            )
        );

        return reset($constructors) ?: null;
    }
}