Fazland - DtoManagementBundle - Param Converter
===============================================
Now that we have a DTO `Resolver` it is easy to have also a ParamConverter.

That is, as the example in the beginning of these docs, you can directly inject the right implementation of the desired DTO into the controller action.

```php

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
```

Next step: [Annotations](./annotations.md)
