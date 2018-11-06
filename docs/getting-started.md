Fazland - DtoManagementBundle
=============================
Think about these points:

- Have you ever struggled against very long controllers' actions?

- Have you ever written services in order to create, read update, delete entities?

- Have you ever hated coupling entities to relative types (i.e. implementing `\Symfony\Component\Form\FormTypeInterface` or `\Symfony\Component\Form\AbstractType`)?

DTOs ([Data Transfer objects](https://martinfowler.com/eaaCatalog/dataTransferObject.html)) are simple objects that can prevent the problems just listed.
It is obvious that this is a Design Pattern and it can be a solution or a methodology for your application. It is not necessary the right or the only way to write your application.

An example
----------
Let's think about a small scenario:
we have to implement a simple CRUD API that let the users manage an item list (create, read, update and delete items).

With DtoManagementBundle the `ItemController` will be really simple, like the following.
```php
<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity;
use App\Model\Interfaces\Item\ItemInterface;
use App\Model\Interfaces\Item\ItemListInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ItemController extends AbstractController
{
    /**
     * Retrieves the Items list.
     *
     * @Route(path="/items", methods={"GET"})
     *
     * @param Request            $request
     * @param ItemListInterface $dto
     *
     * @return Response
     */
    public function listAction(Request $request, ItemListInterface $dto): Response
    {
        $results = $dto->handleRequest($request);

        $response = // serialize the list into a Response

        return $response;
    }

    /**
     * Posts an Item.
     *
     * @Route(path="/items", methods={"POST"})
     *
     * @param Request       $request
     * @param ItemInterface $dto
     *
     * @return Response
     */
    public function postAction(Request $request, ItemInterface $dto): Response
    {
        $result = $dto->handleRequest($request);

        if (! $result instanceof FormInterface) {
            $dto->commit();
        }

        $response = // serialize the item or the form into a Response

        return $response;
    }

    /**
     * Gets an Item.
     *
     * @Route(path="/item/{id}", methods={"GET"})
     *
     * @param Entity\Item\Item $item
     * @param ItemInterface    $dto
     *
     * @return Response
     */
    public function getAction(Entity\Item\Item $item, ItemInterface $dto): Response
    {
        $dto->setItem($item);

        $response = // serialize the item into a Response

        return $response;
    }

    /**
     * Patches an Item.
     *
     * @Route(path="/item/{id}", methods={"PATCH"})
     *
     * @param Request          $request
     * @param Entity\Item\Item $item
     * @param ItemInterface    $dto
     *
     * @return Response
     */
    public function patchAction(Request $request, Entity\Item\Item $item, ItemInterface $dto): Response
    {
        $result = $dto
            ->setItem($item)
            ->handleRequest($request)
        ;

        if (! $result instanceof FormInterface) {
            $dto->commit();
        }

        $response = // serialize the item into a Response

        return $response;
    }

    /**
     * Deletes an Item.
     *
     * @Route(path="/item/{id}", methods={"DELETE"})
     *
     * @param Entity\Item\Item $item
     * @param ItemInterface    $dto
     *
     * @return Response
     */
    public function patchAction(Entity\Item\Item $item, ItemInterface $dto): Response
    {
        $dto->setItem($item);

        $dto->remove();

        $response = // serialize the item into a Response

        return $response;
    }
}

```

Simple, isn't it?

DtoManagementBundle comes with the `ApiModelParamConverter` which will inject in your action the right implementation (the `version`) of the DTO.

In this way you can version your endpoints without changing a single line of code of your controller actions.

Let's see how it is configured and how it works.

- [DTO structure](./dto-structure.md)
- [Configuration](./configuration.md)
- [Nested dtos](./nested-dtos.md)
- [Param Converter](./param-converter.md)
- [DtoManagementBundle annotations](./annotations.md)
