<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\ParamConverter;

use Fazland\DtoManagementBundle\Finder\ServiceLocatorRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $version = $request->attributes->get('_version', date_create()->format('Ymd'));
        $locator = $this->registry->get($configuration->getClass());

        try {
            $request->attributes->set($configuration->getName(), $locator->get($version));
        } catch (ServiceNotFoundException $exception) {
            throw new NotFoundHttpException($configuration->getClass().' object not found for version '.$version.'.');
        } catch (\Throwable $exception) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration): bool
    {
        return $this->registry->has($configuration->getClass());
    }
}
