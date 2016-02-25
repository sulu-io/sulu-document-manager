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

use Doctrine\Common\Inflector\Inflector;
use Sulu\Component\DocumentManager\Behavior\Path\AliasFilingBehavior;
use Sulu\Component\DocumentManager\Event\AbstractEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;

/**
 * Automatically set the parent at a pre-determined location.
 */
class AliasFilingSubscriber extends AbstractFilingSubscriber
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

    /**
     * {@inheritdoc}
     */
    protected function generatePath(PersistEvent $event)
    {
        $currentPath = '';
        if ($event->hasParentNode()) {
            $currentPath = $event->getParentNode()->getPath();
        }
        $parentName = $this->getParentName($event);

        return sprintf('%s/%s', $currentPath, Inflector::pluralize($parentName));
    }

    /**
     * @param object $document
     *
     * @return bool
     */
    protected function supports($document)
    {
        return $document instanceof AliasFilingBehavior;
    }

    /**
     * @param $document
     *
     * @return string
     */
    protected function getParentName(AbstractEvent $event)
    {
        $document = $event->getDocument();

        return $event->getContext()->getMetadataFactory()->getMetadataForClass(get_class($document))->getAlias();
    }
}
