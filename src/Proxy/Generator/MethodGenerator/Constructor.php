<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Proxy\Generator\MethodGenerator;

use ProxyManager\Generator\MethodGenerator;
use ProxyManager\ProxyGenerator\Util\Properties;
use Psr\Container\ContainerInterface;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Reflection\MethodReflection;

class Constructor extends MethodGenerator
{
    /**
     * Constructor.
     *
     * @param \ReflectionClass  $originalClass
     * @param PropertyGenerator $valueHolder
     * @param PropertyGenerator $locator
     * @param Properties|null   $properties
     *
     * @return self
     */
    public static function generateMethod(\ReflectionClass $originalClass, PropertyGenerator $valueHolder, PropertyGenerator $locator, ?Properties $properties = null): self
    {
        $originalConstructor = self::getConstructor($originalClass);
        $originalProperties = $properties ?? Properties::fromReflectionClass($originalClass);

        $constructor = $originalConstructor
            ? self::fromReflection($originalConstructor)
            : new self('__construct');

        $constructor->setParameter(new ParameterGenerator($locator->getName(), ContainerInterface::class));

        $constructor->setDocBlock('{@inheritDoc}');
        $constructor->setBody(
            '$this->'.$valueHolder->getName().' = '.self::generateAnonymousClassValueHolder($originalProperties, $originalClass->getDefaultProperties())."\n"
            .'$this->'.$locator->getName().' = $'.$locator->getName().';'."\n"
            .self::generateUnsetAccessiblePropertiesCode($originalProperties)
            .self::generateOriginalConstructorCall($originalClass)
        );

        return $constructor;
    }

    private static function generateOriginalConstructorCall(\ReflectionClass $class): string
    {
        $originalConstructor = self::getConstructor($class);
        if (null === $originalConstructor) {
            return '';
        }

        $constructor = self::fromReflection($originalConstructor);

        return "\n\n"
            .'parent::'.$constructor->getName().'('
            .\implode(
                ', ',
                \array_map(
                    static function (ParameterGenerator $parameter): string {
                        return ($parameter->getVariadic() ? '...' : '').'$'.$parameter->getName();
                    },
                    $constructor->getParameters()
                )
            )
            .');';
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
        $constructors = \array_map(
            static function (\ReflectionMethod $method): MethodReflection {
                return new MethodReflection(
                    $method->getDeclaringClass()->getName(),
                    $method->getName()
                );
            },
            \array_filter(
                $class->getMethods(),
                static function (\ReflectionMethod $method): bool {
                    return $method->isConstructor();
                }
            )
        );

        return \reset($constructors) ?: null;
    }

    private static function generateAnonymousClassValueHolder(Properties $properties, array $defaults): string
    {
        $accessibleProperties = $properties->getPublicProperties();

        return "new class extends \stdClass {\n".
            \implode("\n", \array_map(static function (\ReflectionProperty $property) use (&$defaults): string {
                $name = $property->getName();

                return '    public $'.$name.(\array_key_exists($name, $defaults) ? ' = '.\var_export($defaults[$name], true) : '').';';
            }, $accessibleProperties))
            ."\n};";
    }

    private static function generateUnsetAccessiblePropertiesCode(Properties $properties): string
    {
        $accessibleProperties = $properties->getPublicProperties();
        if (! $accessibleProperties) {
            return '';
        }

        return  self::generateUnsetStatement($accessibleProperties)."\n\n";
    }

    private static function generateUnsetStatement(array $properties): string
    {
        return 'unset('
            .\implode(
                ', ',
                \array_map(
                    static function (\ReflectionProperty $property): string {
                        return '$this->'.$property->getName();
                    },
                    $properties
                )
            )
            .');';
    }
}
