<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\Fixtures\ModelConverter\Controller;

use Fazland\DtoManagementBundle\Tests\Fixtures\ModelConverter\Model\Interfaces\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

class TestController extends Controller
{
    use VarDumperTestTrait;

    public function indexAction(UserInterface $user): Response
    {
        return new Response(get_class($user));
    }
}
