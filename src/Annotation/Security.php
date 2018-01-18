<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Annotation;

/**
 * @Annotation()
 */
class Security
{
    /**
     * @var string
     *
     * @Required()
     */
    public $expression;

    /**
     * @var string
     */
    public $message;
}