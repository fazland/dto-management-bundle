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

        $parent = GetMethodIfExists::get($originalClass, '__get');
        $valueHolderName = $valueHolder->getName();

        $this->setDocBlock(($parent ? "{@inheritDoc}\n" : '')."@param string \$name\n");

        $callParent = PublicScopeSimulator::getPublicAccessSimulationCode(
            PublicScopeSimulator::OPERATION_GET,
            'name',
            'value',
            $valueHolder,
            'returnValue'
        );

        if (! $publicProperties->isEmpty()) {
            $callParent = \str_replace("\n", "\n    ", $callParent);

            $callParent = <<<PHP
\$camelized = \\lcfirst(\\str_replace(' ', '', \\ucwords(\\str_replace('_', ' ', \$name))));
if (! isset(self::\${$publicProperties->getName()}[\$name])) {
    if (isset(self::\${$publicProperties->getName()}[\$camelized])) {
        \$name = \$camelized;
    }
}

if (isset(self::\${$publicProperties->getName()}[\$name])) {
    \$returnValue = & \$this->$valueHolderName->\$name;
} else if (\\property_exists(\$this, \$camelized)) {
    \$returnValue = & \$this->\$camelized;
} else {
    $callParent
}


PHP;
        }

        $body = $callParent."\n";
        $body .= 'return $returnValue;';

        $this->setBody($body);
    }
}
