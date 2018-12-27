<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Proxy\Generator\MethodGenerator;

use ProxyManager\Generator\MagicMethodGenerator;
use ProxyManager\ProxyGenerator\PropertyGenerator\PublicPropertiesMap;
use ProxyManager\ProxyGenerator\Util\GetMethodIfExists;
use ProxyManager\ProxyGenerator\Util\PublicScopeSimulator;
use ReflectionClass;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

class MagicSet extends MagicMethodGenerator
{
    public function __construct(
        ReflectionClass $originalClass,
        PropertyGenerator $valueHolder,
        PublicPropertiesMap $publicProperties,
        array $propertyInterceptors
    ) {
        parent::__construct(
            $originalClass,
            '__set',
            [new ParameterGenerator('name'), new ParameterGenerator('value')]
        );

        $parent = GetMethodIfExists::get($originalClass, '__set');
        $valueHolderName = $valueHolder->getName();

        $this->setDocBlock(($parent ? "{@inheritDoc}\n" : '')."@param string \$name\n@param mixed \$value\n\n@return mixed");

        $callParent = <<<PHP
\$targetObject = \$this->$valueHolderName;
\$accessor = function & () use (\$targetObject, \$name, \$value) {
    \$targetObject->\$name = \$value;

    return \$targetObject->\$name;
};
\$backtrace = debug_backtrace(true);
\$scopeObject = isset(\$backtrace[1]['object']) ? \$backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
\$accessor = \$accessor->bindTo(\$scopeObject, get_class(\$scopeObject));
\$returnValue = & \$accessor();
PHP;

        if (! $publicProperties->isEmpty()) {
            $callParent = \str_replace("\n", "\n    ", $callParent);

            $callParent = <<<PHP
if (isset(self::\${$publicProperties->getName()}[\$name])) {
    \$returnValue = (\$this->$valueHolderName->\$name = \$value);
} else {
    $callParent
}


PHP;
        }

        $body = <<<PHP
if (! isset(self::\${$publicProperties->getName()}[\$name])) {
    \$camelized = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', \$name))));
    if (isset(self::\${$publicProperties->getName()}[\$camelized])) {
        \$name = \$camelized;
    }
}

PHP;

        $body .= "switch(\$name) {\n";
        foreach ($propertyInterceptors as $propertyName => $interceptors) {
            $interceptors = \array_map(function (string $body): string {
                return \str_replace("\n", "\n        ", $body);
            }, $interceptors);

            $underscoredProperty = self::underscore($propertyName);
            if ($underscoredProperty !== $propertyName) {
                $body .= "    case '$underscoredProperty': \n";
            }

            $body .= "    case '$propertyName': \n";
            $body .= '        '.\implode(";\n        ", $interceptors).";\n        break;\n\n";
        }
        $body .= "}\n\n";

        $body .= $callParent."\n";
        $body .= 'return $returnValue;';

        $this->setBody($body);
    }

    private static function underscore(string $word): string
    {
        return \strtolower(\preg_replace('~(?<=\\w)([A-Z])~', '_$1', $word));
    }
}
