<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\Fixtures\ModelConverter\Controller;

use Fazland\DtoManagementBundle\Proxy\ProxyInterface;
use Fazland\DtoManagementBundle\Tests\Fixtures\ModelConverter\Model\Interfaces\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class TestController extends AbstractController
{
    public function indexAction(UserInterface $user): Response
    {
        return new Response($user instanceof ProxyInterface ? \get_parent_class($user) : \get_class($user));
    }
}
