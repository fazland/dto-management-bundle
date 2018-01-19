<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Proxy\Factory;

use Fazland\DtoManagementBundle\Proxy\Generator\AccessInterceptorGenerator;
use ProxyManager\Factory\AbstractBaseFactory;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;

class AccessInterceptorFactory extends AbstractBaseFactory
{
    /**
     * @var AccessInterceptorGenerator|null
     */
    private $generator;

    /**
     * @inheritdoc
     */
    public function generateProxy(string $className, array $proxyOptions = []) : string
    {
        return parent::generateProxy($className, $proxyOptions);
    }

    /**
     * {@inheritDoc}
     */
    protected function getGenerator() : ProxyGeneratorInterface
    {
        return $this->generator ?: $this->generator = new AccessInterceptorGenerator();
    }
}
