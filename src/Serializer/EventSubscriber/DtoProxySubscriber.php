<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Serializer\EventSubscriber;

use Fazland\DtoManagementBundle\Proxy\ProxyInterface;
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
            $type->name = \get_parent_class($object);
            $type->metadata = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // Cannot use the constant here, as if serializer is non-existent an error would be thrown.
            'serializer.pre_serialize' => ['onPreSerialize', 20],
            PreSerializeEvent::class => ['onPreSerialize', 20],
        ];
    }
}
