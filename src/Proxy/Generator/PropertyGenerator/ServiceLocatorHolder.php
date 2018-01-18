<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Proxy\Generator\PropertyGenerator;

use ProxyManager\Generator\Util\UniqueIdentifierGenerator;
use Zend\Code\Generator\PropertyGenerator;

class ServiceLocatorHolder extends PropertyGenerator
{
    public function __construct()
    {
        parent::__construct(UniqueIdentifierGenerator::getIdentifier('serviceLocator'));

        $this->setVisibility(self::VISIBILITY_PRIVATE);
        $this->setDocBlock('@var \Psr\Container\ContainerInterface Service locator that could resolve services used by this proxy');
    }
}
