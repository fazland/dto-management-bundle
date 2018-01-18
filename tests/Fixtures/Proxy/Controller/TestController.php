<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\Fixtures\Proxy\Controller;

use Fazland\DtoManagementBundle\Tests\Fixtures\Proxy\Model\Interfaces\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

class TestController extends Controller
{
    use VarDumperTestTrait;

    public function indexAction(UserInterface $user): Response
    {
        $user->foobar = 'ciao';
    }

    public function protectedAction(UserInterface $user): Response
    {
        $user->foobar = 'ciao';

        return new Response($this->getDump($user->foobar));
    }
}
