<?php

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Path;

use Sulu\Component\DocumentManager\Behavior\Path\ResetFilingPathBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;

/**
 * Class ResetFilingPathSubscriber.
 */
class ResetFilingPathSubscriber extends AbstractFilingSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => ['handlePersist', 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function generatePath(PersistEvent $event)
    {
        return $this->basePath;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($document)
    {
        return $document instanceof ResetFilingPathBehavior;
    }

    /**
     * {@inheritdoc}
     */
    protected function getParentName($document)
    {
        return '';
    }
}
