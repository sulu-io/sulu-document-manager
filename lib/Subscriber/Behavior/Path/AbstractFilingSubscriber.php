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

use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NodeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Automatically set the parent at a pre-determined location.
 */
abstract class AbstractFilingSubscriber implements EventSubscriberInterface
{
    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @param NodeManager $nodeManager
     */
    public function __construct(
        NodeManager $nodeManager
    ) {
        $this->nodeManager = $nodeManager;
    }

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
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $path = $this->generatePath($event);

        $parentNode = $this->nodeManager->createPath($path);
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
    abstract protected function getParentName($document);
}
