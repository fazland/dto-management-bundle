<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\ParamConverter;

use Fazland\DtoManagementBundle\InterfaceResolver\ResolverInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiModelParamConverter implements ParamConverterInterface
{
    /**
     * @var ResolverInterface
     */
    private $resolver;

    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        try {
            $service = $this->resolver->resolve($configuration->getClass(), $request);
        } catch (ServiceNotFoundException $exception) {
            throw new NotFoundHttpException($configuration->getClass().' object not found for version '.$exception->getId().'.');
        }

        $request->attributes->set($configuration->getName(), $service);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration): bool
    {
        $class = $configuration->getClass();

        return null !== $class && $this->resolver->has($class);
    }
}
