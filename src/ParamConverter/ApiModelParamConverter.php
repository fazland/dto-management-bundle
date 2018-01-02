<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\ParamConverter;

use Fazland\DtoManagementBundle\Model\Finder\ServiceLocatorRegistry;
use Cake\Chronos\Chronos;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class ApiModelParamConverter implements ParamConverterInterface
{
    /**
     * @var ServiceLocatorRegistry
     */
    private $registry;

    public function __construct(ServiceLocatorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $version = $request->attributes->get('_version', Chronos::now()->format('Ymd'));
        $locator = $this->registry->get($configuration->getClass());

        try {
            $request->attributes->set($configuration->getName(), $locator->get($version));
        } catch (\Throwable $exception) {
            return false;
        }

        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        return $this->registry->has($configuration->getClass());
    }
}
