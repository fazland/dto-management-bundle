<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\Fixtures\Proxy\Transformer;

class TestTransform
{
    public function reverseTransform($value)
    {
        return strtoupper($value);
    }
}