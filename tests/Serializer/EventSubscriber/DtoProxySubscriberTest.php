<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\Serializer\EventSubscriber;

use Fazland\DtoManagementBundle\Serializer\EventSubscriber\DtoProxySubscriber;
use Fazland\DtoManagementBundle\Tests\Fixtures\Model\FooProxy;
use Kcs\Serializer\EventDispatcher\Events;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Kcs\Serializer\Type\Type;
use PHPUnit\Framework\TestCase;

class DtoProxySubscriberTest extends TestCase
{
    /**
     * @var DtoProxySubscriber
     */
    private $subscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->subscriber = new DtoProxySubscriber();
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals([Events::PRE_SERIALIZE => ['onPreSerialize', 20]], DtoProxySubscriber::getSubscribedEvents());
    }

    public function getNonProxyValues(): iterable
    {
        yield [0];
        yield [0.0];
        yield [false];
        yield ['string'];
        yield [[]];
        yield [new \stdClass()];
    }

    /**
     * @dataProvider getNonProxyValues
     *
     * @param mixed $value
     */
    public function testOnPreSerializeShouldNotActOnNonProxy($value): void
    {
        $event = $this->prophesize(PreSerializeEvent::class);

        $event->getData()->willReturn($value);
        $event->getType()->shouldNotBeCalled();

        $this->subscriber->onPreSerialize($event->reveal());
    }

    public function testOnPreSerializeShouldSetProxyParentClassName(): void
    {
        $event = $this->prophesize(PreSerializeEvent::class);

        $proxy = new FooProxy();

        $type = $this->prophesize(Type::class);
        $type->is(\get_class($proxy))->willReturn(true);

        $event->getData()->willReturn($proxy);
        $event->getType()->willReturn($type);

        $type->setName(\get_parent_class($proxy))->shouldBeCalled();

        $this->subscriber->onPreSerialize($event->reveal());
    }
}
