<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use PHPCR\NodeInterface;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Component\DocumentManager\Collection\ChildrenCollection;
use Sulu\Component\DocumentManager\Collection\ReferrerCollection;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handle creation of proxies.
 */
class ProxyFactory
{
    /**
     * @var LazyLoadingGhostFactory
     */
    private $proxyFactory;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @param LazyLoadingGhostFactory $proxyFactory
     * @param EventDispatcherInterface $dispatcher
     * @param MetadataFactoryInterface $metadataFactory
     */
    public function __construct(
        DocumentManagerInterface $documentManager,
        LazyLoadingGhostFactory $proxyFactory,
        MetadataFactoryInterface $metadataFactory
    ) {
        $this->documentManager = $documentManager;
        $this->proxyFactory = $proxyFactory;
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Create a new proxy object from the given document for
     * the given target node.
     *
     * TODO: We only pass the document here in order to correctly evaluate its locale
     *       later. I wonder if it necessary.
     *
     * @param object $fromDocument
     * @param NodeInterface $targetNode
     * @param array $options
     *
     * @return \ProxyManager\Proxy\GhostObjectInterface
     */
    public function createProxyForNode($fromDocument, NodeInterface $targetNode, $options = [])
    {
        $registry = $this->documentManager->getContext()->getDocumentRegistry();

        // if node is already registered then just return the registered document
        if ($registry->hasNode($targetNode)) {
            $document = $registry->getDocumentForNode($targetNode);
            $locale = $registry->getOriginalLocaleForDocument($fromDocument);

            // If the parent is not loaded in the correct locale, reload it in the correct locale.
            if ($registry->getOriginalLocaleForDocument($document) !== $locale) {
                $hydrateEvent = new HydrateEvent($this->documentManager->getContext(), $targetNode, $locale);
                $hydrateEvent->setDocument($document);
                $this->documentManager->getContext()->getEventDispatcher()->dispatch(Events::HYDRATE, $hydrateEvent);
            }

            return $document;
        }

        $initializer = function (LazyLoadingInterface $document, $method, array $parameters, &$initializer) use (
            $fromDocument,
            $targetNode,
            $options,
            $registry
        ) {
            $locale = $registry->getOriginalLocaleForDocument($fromDocument);

            $hydrateEvent = new HydrateEvent($this->documentManager->getContext(), $targetNode, $locale, $options);
            $hydrateEvent->setDocument($document);
            $this->documentManager->getContext()->getEventDispatcher()->dispatch(Events::HYDRATE, $hydrateEvent);

            $initializer = null;
        };

        $targetMetadata = $this->metadataFactory->getMetadataForPhpcrNode($targetNode);
        $proxy = $this->proxyFactory->createProxy($targetMetadata->getClass(), $initializer);
        $locale = $registry->getOriginalLocaleForDocument($fromDocument);
        $registry->registerDocument($proxy, $targetNode, $locale);

        return $proxy;
    }

    /**
     * Create a new children collection for the given document.
     *
     * @param object $document
     *
     * @return ChildrenCollection
     */
    public function createChildrenCollection($document, array $options = [])
    {
        $registry = $this->documentManager->getContext()->getDocumentRegistry();
        $node = $registry->getNodeForDocument($document);
        $locale = $registry->getOriginalLocaleForDocument($document);

        return new ChildrenCollection(
            $node,
            $this->documentManager,
            $locale,
            $options
        );
    }

    /**
     * Create a new collection of referrers from a list of referencing items.
     *
     * @param $document
     *
     * @return ReferrerCollection
     */
    public function createReferrerCollection($document)
    {
        $registry = $this->documentManager->getRegistry();
        $node = $registry->getNodeForDocument($document);
        $locale = $registry->getOriginalLocaleForDocument($document);

        return new ReferrerCollection(
            $node,
            $this->documentManager,
            $locale
        );
    }
}
