<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Path;

use Sulu\Component\DocumentManager\Event\AbstractEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Automatically set the parent at a pre-determined location.
 */
abstract class AbstractFilingSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => ['handlePersist', 490],
        ];
    }

    public function handlePersist(PersistEvent $event)
    {
        $options = $event->getOptions();

        // if "path" option has been explicitly set then it takes precedence
        // over this subscriber.
        //
        // here we SHOULD say if the event already has a parent node then
        // return, however unfortunately in Sulu the StructureFilingSubscriber
        // depends upon the state of the parent node as set by the preceding
        // AliasFilingSubscriber. This means that it depends on there being a
        // parent node in the event and that returning based on the existence
        // of the parent node changes breaks the system.
        //
        // see: https://github.com/sulu-io/sulu/issues/2117
        if (isset($options['path']) || isset($options['parent_path'])) {
            return;
        }

        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $path = $this->generatePath($event);

        $parentNode = $event->getManager()->getNodeManager()->createPath($path);
        $event->setParentNode($parentNode);
    }

    /**
     * Generates the path for the given event.
     *
     * @return string
     */
    abstract protected function generatePath(PersistEvent $event);

    /**
     * Return true if this subscriber should be applied to the document.
     *
     * @param object $document
     */
    abstract protected function supports($document);

    /**
     * Return the name of the parent document.
     *
     * @param $document
     *
     * @return string
     */
    abstract protected function getParentName(AbstractEvent $event);
}
