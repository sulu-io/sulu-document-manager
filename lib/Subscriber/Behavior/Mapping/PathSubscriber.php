<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping;

use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set the path on the document.
 */
class PathSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => 'handleHydrate',
        ];
    }

    /**
     * @param HydrateEvent $event
     */
    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof PathBehavior) {
            return;
        }

        $event->getAccessor()->set(
            'path',
            $event->getContext()->getInspector()->getPath($document)
        );
    }
}
