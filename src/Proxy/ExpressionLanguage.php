<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Proxy;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;

class ExpressionLanguage extends BaseExpressionLanguage
{
    protected function registerFunctions()
    {
        $this->addFunction(ExpressionFunction::fromPhp('constant'));

        $this->register('is_granted', function ($attributes, $object = 'null') {
            return sprintf('$auth_checker->isGranted(%s, %s)', $attributes, $object);
        }, function (array $variables, $attributes, $object = null) {
            return $variables['auth_checker']->isGranted($attributes, $object);
        });
    }
}
