<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Phpcr;

use PHPCR\PropertyInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentReferencedException;
use Sulu\Component\DocumentManager\NodeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Remove subscriber.
 */
class RemoveSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    public function __construct(
        DocumentRegistry $documentRegistry,
        NodeManager $nodeManager
    ) {
        $this->documentRegistry = $documentRegistry;
        $this->nodeManager = $nodeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::REMOVE => ['handleRemove', 500],
        ];
    }

    /**
     * Remove the given documents node from PHPCR session and optionally
     * remove any references to the node.
     *
     * @param RemoveEvent $event
     *
     * @throws DocumentReferencedException
     */
    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();
        $node = $this->documentRegistry->getNodeForDocument($document);
        $referenceFound = false;

        // still delivers properties, which had a reference on the node at the beginning of the session
        $references = iterator_to_array($node->getReferences());

        // this is necessary because of the getReferences behavior
        foreach ($references as $reference) {
            /** @var PropertyInterface $reference */
            if (array_search($node->getIdentifier(), $reference->getValue()) !== false) {
                $referenceFound = true;
                break;
            }
        }

        if ($referenceFound) {
            throw new DocumentReferencedException($document, $references);
        }

        $node->remove();
    }
}
