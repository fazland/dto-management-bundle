Fazland - DtoManagementBundle - DTO structure
=============================================
DtoManagementBundle has an opiniated way to implement DTOs:
- Choose a namespace (or multiple namespaces) that will contain your DTOs
- Inside the namespaces create subnamespace containing the interfaces of the DTOs and another one containing all the implementations.

Example:
`App\Model` is the base namespace configured
`App\Model\Interfaces` is the subnamespace containing all the interfaces
`App\Model\v2018` is the subnamespace containing all 2018th implementations.

Why 2018? Because we structure the DTOs' versions based on release date in production of the DTOs.
This means that `v2018` will contain ALL 2018 released DTOs.

###### But how are the DTOs implementation structured?

Use a date as the version: `App\Model\v2018\v20181014`.

Inside this namespace you can put all your implementation of your interfaces.

:warning:&nbsp;-- Namespace naming -- :warning:
---------------------------------------------------------
At this moment the only way to version the DTOs is using the date in theirs namespace. This a known limitation although is the recommended way.

Example
-------
Namespace | Directory structure
--------- | -------------------
`App\Model` | src/Model
`App\Model\Interfaces` | src/Model/Interfaces
`App\Model\Interfaces\ItemInterface` | src/Model/Interfaces/ItemInterface.php
`App\Model\Interfaces\ItemListInterface` | src/Model/Interfaces/ItemListInterface.php
`App\Model\v2018` | src/Model/v2018
`App\Model\v2018\v20181014` | src/Model/v2018/v20181014
`App\Model\v2018\v20181014\Item` | src/Model/v2018/v20181014/Item
`App\Model\v2018\v20181014\Item\Item` | src/Model/v2018/v20181014/Item/Item.php
`App\Model\v2018\v20181014\Item\ItemList` | src/Model/v2018/v20181014/Item/ItemList.php

Implementing a new version of the `Item` is simple. You have 2 ways:
- extend `App\Model\v2018\v20181014\Item\Item` and just override / replace what you want
- create another class that implements the `ItemInterface` and have a completely different DTO.

Next step: [Configuration](./configuration.md)
