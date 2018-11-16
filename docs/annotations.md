Fazland - DtoManagementBundle - Annotations
===========================================
DtoManagementBundle comes with 2 really nice annotations:
- `Fazland\DtoManagementBundle\Annotation\Security` (`@Security`)
- `Fazland\DtoManagementBundle\Annotation\Transform` (`@Transform`)

Security Annotation
-------------------
The `Security` annotation is similar to the Symfony's Security:

Security annotations signature has 3 parameters:
- `expression`: a security boolean expression (required)
- `message`: string
- `onInvalid`: what to do if expression evaluates to false (defaults to `access_denied`)

This means that you can check the current user or whatever you want and decide if it is possible to access the annotated property/method.

```php
namespace App\Model\v2018\v20181115;

use App\Model\Interfaces\YourDtoInterface;
use Fazland\DtoManagementBundle\Annotation\Security;

class YourDto implements YourDtoInterface
{
    /**
     * @Security("is_granted('ROLE_USER')", onInvalid="null")
     */
    public $property1;

    /**
     * @Security("is_granted('ROLE_USER')")
     */
    public $property2;

    /**
     * @Security("is_granted('ROLE_USER'), onInvalid="null")
     */
    public function method()
    {
        // [...]
        // do your stuff
        // [...]

        return $theReturnValue;
    }
}
```

When accessing `$property1`, if the boolean condition evaluates to `false` the value returned is `null`.
On `$property2`, instead, if the boolean condition evaluates to `false` it will throw a `\Symfony\Component\Security\Core\Exception\AccessDeniedException`.

Transform Annotation
--------------------
The `Transform` annotation is a very useful annotation: it permits to use a data transformer and retrieve directly the transformed value while accessing a property/method.

Example:
```php
namespace App\Form\DataTransformer\AnotherEntityTransformer;

use App\Entity\AnotherEntity;
use Symfony\Component\Form\DataTransformerInterface;

class AnotherEntityTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        // it is not used, so I won't implement it.
    }

    public function reverseTransform($value)
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof AnotherEntity) {
            return $value;
        }

        // [...]
        // check and try to reverseTransform $value in order to have an instance of AnotherEntity
        // [...]

        return $valueTransformed;
    }
}
```

```php
namespace App\Model\v2018\v20181115;

use App\Entity\AnotherEntity;
use App\Model\Interfaces\AnotherDto;
use App\Form\DataTransformer\AnotherEntityTransformer;
use Fazland\DtoManagementBundle\Annotation\Transform;

class AnotherDto implements AnotherDtoInterface
{
    /**
     * @var AnotherEntity
     *
     * @Transform(AnotherEntityTransformer::class)
     */
    public $anotherEntity;
}
```

When you access the `AnotherDto::$anotherEntity` property the `DataTransformerInterface::reverseTransform()` will be called.
That is, you will have the transformed value! Simple, isn't it?

###### But how this can be done?
Internally, `DtoManagementBundle` creates `Proxies` of our DTOS (and stores them in cache). With these proxies we can call transformations and/or do security checks.

That's all, folks!
