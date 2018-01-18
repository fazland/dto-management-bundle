<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Proxy\Generator\PropertyGenerator;

use ProxyManager\Generator\MethodGenerator;
use Psr\Container\ContainerInterface;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

class ServiceLocatorHolderSetter extends MethodGenerator
{
    public function __construct(PropertyGenerator $locatorHolder)
    {
        parent::__construct('setProxyServiceLocatorHolder');

        $this->setParameters([
            new ParameterGenerator('container', ContainerInterface::class)
        ]);

        $this->setDocBlock('@required');
        $this->setBody('$this->'.$locatorHolder->getName().' = $container;');
    }
}