<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\DependencyInjection;

use Fazland\DtoManagementBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->processor = new Processor();
    }

    public function testNoDefaultConfigShouldThrow(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->getConfiguration([
            'namespaces' => [],
        ]);
    }

    public function testConfig(): void
    {
        $expected = [
            'namespaces' => [
                'App\\Model',
                'App\\Model',
            ],
            'exclude' => [],
        ];

        $config = $this->getConfiguration([
            'namespaces' => [
                'App\\Model',
                'App\\Model',
            ],
        ]);

        self::assertEquals($expected, $config);
    }

    private function getConfiguration(array $configArray): array
    {
        $configuration = new Configuration();

        return $this->processor->processConfiguration($configuration, [$configArray]);
    }
}
