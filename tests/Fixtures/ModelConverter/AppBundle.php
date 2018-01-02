<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\Fixtures\ModelConverter;

use Fazland\DtoManagementBundle\DependencyInjection\Compiler\FindModelClassesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new FindModelClassesPass(__NAMESPACE__.'\\Model', __DIR__.'/Model'));
    }
}
