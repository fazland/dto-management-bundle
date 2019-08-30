<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\Fixtures\Proxy\Controller;

use Fazland\DtoManagementBundle\Tests\Fixtures\Proxy\Model\Interfaces\UserInterface;
use Fazland\DtoManagementBundle\Tests\Fixtures\Proxy\SemVerModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

class TestController extends AbstractController
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

    public function underscoredAction(UserInterface $user): Response
    {
        $user->bar_bar = 'test_one';

        return new Response($this->getDump($user->barBar));
    }

    public function camelizedAction(UserInterface $user): Response
    {
        $user->barBar = 'test_two';

        return new Response($this->getDump($user->bar_bar));
    }

    public function camelizedPublicAction(UserInterface $user): Response
    {
        $tmp = $user->bar_public;
        $user->barPublic = 'test_two';

        return new Response($this->getDump($tmp).$this->getDump($user->bar_public));
    }

    public function unavailableAction(UserInterface $user): Response
    {
        return new Response($this->getDump($user->getTest()));
    }

    public function semverAction(SemVerModel\Interfaces\UserInterface $user): Response
    {
        return new Response($this->getDump($user->getFoo()));
    }
}
