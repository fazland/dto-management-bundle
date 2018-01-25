<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Proxy\Generator\MethodGenerator;

use ProxyManager\Generator\MagicMethodGenerator;
use ProxyManager\ProxyGenerator\LazyLoadingValueHolder\PropertyGenerator\ValueHolderProperty;
use ProxyManager\ProxyGenerator\PropertyGenerator\PublicPropertiesMap;
use ProxyManager\ProxyGenerator\Util\GetMethodIfExists;
use ProxyManager\ProxyGenerator\Util\PublicScopeSimulator;
use ReflectionClass;
use Zend\Code\Generator\ParameterGenerator;

class MagicIsset extends MagicMethodGenerator
{
    public function __construct(
        ReflectionClass $originalClass,
        ValueHolderProperty $valueHolder,
        PublicPropertiesMap $publicProperties
    ) {
        parent::__construct($originalClass, '__isset', [new ParameterGenerator('name')]);

        $parent = GetMethodIfExists::get($originalClass, '__isset');
        $this->setDocBlock(($parent ? "{@inheritDoc}\n" : '').'@param string $name');

        $callParent = '$returnValue = & parent::__isset($name);';

        if (! $parent) {
            $callParent = PublicScopeSimulator::getPublicAccessSimulationCode(
                PublicScopeSimulator::OPERATION_ISSET,
                'name',
                null,
                $valueHolder,
                'returnValue'
            );
        }

        $body = $callParent."\n";
        $body .= 'return $returnValue;';

        $this->setBody($body);
    }
}
