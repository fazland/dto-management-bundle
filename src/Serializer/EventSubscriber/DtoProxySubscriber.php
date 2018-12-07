<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Serializer\EventSubscriber;

use Fazland\DtoManagementBundle\Proxy\ProxyInterface;
use Kcs\Serializer\EventDispatcher\Events;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DtoProxySubscriber implements EventSubscriberInterface
{
    public function onPreSerialize(PreSerializeEvent $event): void
    {
        $object = $event->getData();

        if (! $object instanceof ProxyInterface) {
            return;
        }

        $type = $event->getType();
        if ($type->is(\get_class($object))) {
            $type->setName(\get_parent_class($object));
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_SERIALIZE => ['onPreSerialize', 20],
        ];
    }
}
