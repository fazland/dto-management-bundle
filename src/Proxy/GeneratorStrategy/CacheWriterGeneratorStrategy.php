<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Proxy\GeneratorStrategy;

use ProxyManager\Configuration;
use ProxyManager\GeneratorStrategy\GeneratorStrategyInterface;
use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Resource\ReflectionClassResource;
use Zend\Code\Generator\ClassGenerator;

class CacheWriterGeneratorStrategy implements GeneratorStrategyInterface
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var \Closure
     */
    private $emptyErrorHandler;

    public function __construct(Configuration $configuration, bool $debug)
    {
        $this->configuration = $configuration;
        $this->debug = $debug;
        $this->emptyErrorHandler = function () { };
    }

    /**
     * @inheritdoc
     */
    public function generate(ClassGenerator $classGenerator): string
    {
        $className = trim($classGenerator->getNamespaceName(), '\\') . '\\' . trim($classGenerator->getName(), '\\');
        $fileName = $this->configuration->getProxiesTargetDir().DIRECTORY_SEPARATOR.str_replace('\\', '', $className).'.php';

        $cacheFactory = new ConfigCacheFactory($this->debug);
        $cache = $cacheFactory->cache($fileName, function (ConfigCacheInterface $cache) use ($classGenerator) {
            $superClass = $classGenerator->getExtendedClass();
            $cache->write('<?php '.$classGenerator->generate(), [new ReflectionClassResource(new \ReflectionClass($superClass))]);
        });

        set_error_handler($this->emptyErrorHandler);

        try {
            require $cache->getPath();
        } finally {
            restore_error_handler();
        }

        return '';
    }
}
