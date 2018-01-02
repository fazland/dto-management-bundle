<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\DependencyInjection;

use Fazland\DtoManagementBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @var Processor
     */
    private $processor;

    protected function setUp(): void
    {
        $this->processor = new Processor();
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testNoDefaultConfigShouldThrow(): void
    {
        $this->getConfiguration([
            'namespaces' => [],
        ]);
    }

    public function testConfig(): void
    {
        $expected = [
            'namespaces' => [
                [
                    'namespace' => 'App\\Model',
                    'base_dir' => 'path/to/base/dir',
                ],
                [
                    'namespace' => 'App\\Model',
                    'base_dir' => 'path/to/base/dir',
                ],
            ],
        ];

        $config = $this->getConfiguration([
            'namespaces' => [
                [
                    'namespace' => 'App\\Model',
                    'base_dir' => 'path/to/base/dir',
                ],
                [
                    'namespace' => 'App\\Model',
                    'base_dir' => 'path/to/base/dir',
                ],
            ],
        ]);

        $this->assertEquals($expected,  $config);
    }

    private function getConfiguration(array $configArray): array
    {
        $configuration = new Configuration();

        return $this->processor->processConfiguration($configuration, [$configArray]);
    }
}
