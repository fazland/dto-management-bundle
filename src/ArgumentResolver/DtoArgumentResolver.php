<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\ArgumentResolver;

use Fazland\DtoManagementBundle\InterfaceResolver\ResolverInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DtoArgumentResolver implements ArgumentValueResolverInterface
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
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $class = $argument->getType();

        return null !== $class && $this->resolver->has($class);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        try {
            yield $this->resolver->resolve($argument->getType(), $request);
        } catch (ServiceNotFoundException $exception) {
            throw new NotFoundHttpException($argument->getType().' object not found for version '.$exception->getId().'.');
        }
    }
}
