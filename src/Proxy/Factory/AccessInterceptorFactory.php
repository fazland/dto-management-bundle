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
     * Change visibility of generateProxy method (protected -> public).
     *
     * {@inheritdoc}
     */
    public function generateProxy(string $className, array $proxyOptions = []): string
    {
        return parent::generateProxy($className, $proxyOptions);
    }

    /**
     * @param AccessInterceptorGenerator $generator
     */
    public function setGenerator(AccessInterceptorGenerator $generator): void
    {
        $this->generator = $generator;
    }

    /**
     * {@inheritdoc}
     */
    protected function getGenerator(): ProxyGeneratorInterface
    {
        return $this->generator ?: $this->generator = new AccessInterceptorGenerator();
    }
}
