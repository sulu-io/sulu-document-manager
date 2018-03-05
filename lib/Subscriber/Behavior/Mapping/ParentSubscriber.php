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

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\ProxyFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set the parent and children on the document.
 */
class ParentSubscriber implements EventSubscriberInterface
{
    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @param ProxyFactory $proxyFactory
     * @param DocumentInspector $inspector
     * @param DocumentManagerInterface $documentManager
     */
    public function __construct(
        ProxyFactory $proxyFactory,
        DocumentInspector $inspector,
        DocumentManagerInterface $documentManager
    ) {
        $this->proxyFactory = $proxyFactory;
        $this->inspector = $inspector;
        $this->documentManager = $documentManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => 'handleHydrate',
            Events::PERSIST => [
                ['handleChangeParent', 0],
                ['handleSetParentNodeFromDocument', 490],
            ],
            Events::MOVE => 'handleMove',
        ];
    }

    /**
     * @param MoveEvent $event
     */
    public function handleMove(MoveEvent $event)
    {
        $document = $event->getDocument();
        $node = $this->inspector->getNode($event->getDocument());
        $this->mapParent($document, $node);
    }

    /**
     * @param PersistEvent $event
     */
    public function handleSetParentNodeFromDocument(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ParentBehavior) {
            return;
        }

        if ($event->hasParentNode()) {
            return;
        }

        $parentDocument = $document->getParent();

        if (!$parentDocument) {
            return;
        }

        $parentNode = $this->inspector->getNode($parentDocument);
        $event->setParentNode($parentNode);
    }

    /**
     * @param HydrateEvent $event
     */
    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ParentBehavior) {
            return;
        }

        $node = $event->getNode();

        if (0 == $node->getDepth()) {
            throw new \RuntimeException(sprintf(
                'Cannot apply parent behavior to root node "%s" with type "%s" for document of class "%s"',
                $node->getPath(),
                $node->getPrimaryNodeType()->getName(),
                get_class($document)
            ));
        }

        $this->mapParent($document, $node, $event->getOptions());
    }

    /**
     * @param PersistEvent $event
     */
    public function handleChangeParent(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ParentBehavior) {
            return;
        }

        $node = $this->inspector->getNode($document);
        $parentNode = $event->getParentNode();

        if ($parentNode->getPath() === $node->getParent()->getPath()) {
            return;
        }

        $this->documentManager->move($document, $parentNode->getPath());
    }

    /**
     * Map parent document to given document.
     *
     * @param object $document child-document
     * @param NodeInterface $node to determine parent
     * @param array $options options to load parent
     */
    private function mapParent($document, NodeInterface $node, $options = [])
    {
        // TODO: performance warning: We are eagerly fetching the parent node
        $targetNode = $node->getParent();

        // Do not map non-referenceable parent nodes
        if (!$targetNode->hasProperty('jcr:uuid')) {
            return;
        }

        $document->setParent($this->proxyFactory->createProxyForNode($document, $targetNode, $options));
    }
}
