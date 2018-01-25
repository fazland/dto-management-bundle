<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Proxy\Generator\MethodGenerator;

use ProxyManager\Generator\MethodGenerator;
use ProxyManager\ProxyGenerator\Util\GetMethodIfExists;

class GetSubscribedServices extends MethodGenerator
{
    public function __construct(\ReflectionClass $originalClass, array $subscribedServices)
    {
        parent::__construct('getSubscribedServices', []);
        $this->setStatic(true);

        $parent = GetMethodIfExists::get($originalClass, 'getSubscribedServices');
        $this->setDocBlock("{@inheritDoc}\n");

        $callParent = null !== $parent ? "\$parentServices = parent::getSubscribedServices();\n" : "\$parentServices = [];\n";
        $subscribedServices = var_export($subscribedServices, true);

        $body = <<<PHP
$callParent
return array_merge($subscribedServices, \$parentServices);
PHP;

        $this->setBody($body);
    }
}
