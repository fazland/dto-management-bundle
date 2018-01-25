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

        $callParent = PublicScopeSimulator::getPublicAccessSimulationCode(
            PublicScopeSimulator::OPERATION_SET,
            'name',
            'value',
            $valueHolder,
            'returnValue'
        );

        if (! $publicProperties->isEmpty()) {
            $callParent = 'if (isset(self::$'.$publicProperties->getName()."[\$name])) {\n"
                .'    $returnValue = ($this->'.$valueHolderName.'->$name = $value);'
                ."\n} else {\n    $callParent\n}\n\n";
        }

        $body = "switch(\$name) {\n";
        foreach ($propertyInterceptors as $propertyName => $interceptors) {
            $interceptors = array_map(function (string $body): string {
                return str_replace("\n", "\n        ", $body);
            }, $interceptors);

            $body .= "    case '$propertyName': \n";
            $body .= '        '.implode(";\n        ", $interceptors).";\n        break;\n\n";
        }
        $body .= "}\n\n";

        $body .= $callParent."\n";
        $body .= 'return $returnValue;';

        $this->setBody($body);
    }
}
