Fazland - DtoManagementBundle - Nested DTOs
===========================================
I have implemented my own DTOs. Now I need to reuse one in another (i.e. I have created a `MoneyInterface` DTO that contains the currency and the actual value):

How can I use it?

`Fazland\DtoManagementBundle\InterfaceResolver\ResolverInterface`
-----------------------------------------------------------------
The resolvers are factories of our DTOs. In fact, given the interface, you can ask the registry if it has an implementation registered or you can retrieve it.

```php
    /**
     * Resolve the given interface and return the corresponding
     * service from the service container.
     *
     * @param string  $interface
     * @param Request $request
     *
     * @return mixed
     */
    public function resolve(string $interface, ?Request $request = null);

    /**
     * Checks whether the given interface could be resolved.
     *
     * @param string $interface
     *
     * @return bool
     */
    public function has(string $interface): bool;
```

Note the use of the Symfony `Request`.

As it has already been said, the DTOs structure is versioned. The `ResolverInterface` uses the `Request` to retrieve the version.
The recommended way is to set a request attribute named `_version`. If not set, the `Resolver` will use the current date.

This date (formatted `Ymd`) will be the criteria while choosing the right implementation. That is, the `Resolver` will resolve the last DTO created before (or in the same day) that day.

Example
-------
Namespace | Directory structure
--------- | -------------------
`App\Model\Interfaces\ItemInterface` | src/Model/Interfaces/ItemInterface.php
`App\Model\v2018\v20181014\Item\Item` | src/Model/v2018/v20181014/Item/Item.php
`App\Model\v2018\v20181105\Item\Item` | src/Model/v2018/v20181105/Item/Item.php

We have two implementations of the `ItemInterface` and we want to resolve an `Item` DTO.

If we specify `20181028` as the `_version` request attribute, the `App\Model\v2018\v20181014\Item\Item` will be the result (`20181028` is before `20181105`).
If we specify `20181115` the other `Item` will be resolved (`App\Model\v2018\v20181105\Item\Item`).

Next step: [Param Converter](./param-converter.md)
