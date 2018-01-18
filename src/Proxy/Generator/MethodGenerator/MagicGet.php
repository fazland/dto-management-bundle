<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Proxy\Generator\MethodGenerator;

use ProxyManager\Generator\MagicMethodGenerator;
use ProxyManager\ProxyGenerator\LazyLoadingValueHolder\PropertyGenerator\ValueHolderProperty;
use ProxyManager\ProxyGenerator\PropertyGenerator\PublicPropertiesMap;
use ProxyManager\ProxyGenerator\Util\GetMethodIfExists;
use ProxyManager\ProxyGenerator\Util\PublicScopeSimulator;
use ReflectionClass;
use Zend\Code\Generator\ParameterGenerator;

class MagicGet extends MagicMethodGenerator
{
    public function __construct(
        ReflectionClass $originalClass,
        ValueHolderProperty $valueHolder,
        PublicPropertiesMap $publicProperties
    ) {
        parent::__construct(
            $originalClass,
            '__get',
            [new ParameterGenerator('name')]
        );

        $parent          = GetMethodIfExists::get($originalClass, '__get');
        $valueHolderName = $valueHolder->getName();

        $this->setDocBlock(($parent ? "{@inheritDoc}\n" : '') . "@param string \$name\n");

        $callParent = PublicScopeSimulator::getPublicAccessSimulationCode(
            PublicScopeSimulator::OPERATION_GET,
            'name',
            'value',
            $valueHolder,
            'returnValue'
        );

        if (! $publicProperties->isEmpty()) {
            $callParent = 'if (isset(self::$' . $publicProperties->getName() . "[\$name])) {\n"
                . '    $returnValue = & $this->' . $valueHolderName . '->$name;'
                . "\n} else {\n    $callParent\n}\n\n";
        }

        $body = $callParent."\n";
        $body .= 'return $returnValue;';

        $this->setBody($body);
    }
}