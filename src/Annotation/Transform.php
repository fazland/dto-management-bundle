<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Annotation;

/**
 * @Annotation()
 */
class Transform
{
    /**
     * @var string
     *
     * @Required()
     */
    public $service;
}
