<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Audit;

use Sulu\Component\DocumentManager\Behavior\Audit\TimestampBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Manage the timestamp (created, changed) fields on
 * documents before they are persisted.
 */
class TimestampSubscriber implements EventSubscriberInterface
{
    const CREATED = 'created';
    const CHANGED = 'changed';

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => 'handlePersist',
            Events::METADATA_LOAD => 'handleMetadataLoad',
        ];
    }

    /**
     * @param MetadataLoadEvent
     */
    public function handleMetadataLoad($event)
    {
        if (!$event->getMetadata()->getReflectionClass()->isSubclassOf(TimestampBehavior::class)) {
            return;
        }

        $metadata = $event->getMetadata();
        $metadata->addFieldMapping(
            self::CREATED,
            [
                'encoding' => 'system_localized',
                'property' => self::CREATED,
            ]
        );
        $metadata->addFieldMapping(
            self::CHANGED,
            [
                'encoding' => 'system_localized',
                'property' => self::CHANGED,
            ]
        );
    }

    /**
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof TimestampBehavior) {
            return;
        }

        $locale = $event->getLocale();

        if (!$locale) {
            return;
        }

        if (!$document->getCreated()) {
            $event->getAccessor()->set(self::CREATED, new \DateTime());
        }

        $event->getAccessor()->set(self::CHANGED, new \DateTime());
    }
}
