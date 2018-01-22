<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Annotation;

/**
 * @Annotation()
 */
class Security
{
    const ACCESS_DENIED_EXCEPTION = 'access_denied';
    const RETURN_NULL = 'null';

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

    /**
     * @var string
     *
     * @Enum({"access_denied", "null"})
     */
    public $onInvalid = self::ACCESS_DENIED_EXCEPTION;
}