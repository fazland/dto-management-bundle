<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\Fixtures\Proxy\Model\v2017\v20171215;

use Fazland\DtoManagementBundle\Annotation\Security;
use Fazland\DtoManagementBundle\Annotation\Transform;
use Fazland\DtoManagementBundle\Tests\Fixtures\Proxy\Model\Interfaces\UserInterface;
use Fazland\DtoManagementBundle\Tests\Fixtures\Proxy\Transformer\TestTransform;

class User implements UserInterface
{
    /**
     * @Transform(TestTransform::class)
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public $foobar = 'ciao';

    public function __construct()
    {
    }

    /**
     * @Transform(TestTransform::class)
     * @Security("value == 'ciao'")
     */
    public function setFoo($value)
    {
        $this->foo = $value;
    }

    public function getFoo()
    {
        return 'test';
    }

    public function setBar()
    {
        $this->foobar = 'testtest';
    }

    public function fluent(): self
    {
        return $this;
    }

    /**
     * @Security("is_granted('ROLE_DENY')", onInvalid="null")
     */
    public function getTest(): ?string
    {
        return 'unavailable_test';
    }
}
