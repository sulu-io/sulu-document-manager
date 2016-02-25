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

use PHPCR\SessionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This class is available to event subscribers and contains all of the
 * dependencies that CONCERN a particular instance of the document manager
 * which emitted the event.
 */
class DocumentManagerContext
{
    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $inspectorFactory;

    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * @var SessionInterface
     */
    private $phpcrSession;

    public function __construct(
        DocumentManagerInterface $documentManager,
        MetadataFactoryInterface $metadataFactory,
        NodeManager $nodeManager,
        DocumentRegistry $documentRegistry,
        EventDispatcherInterface $eventDispatcher,
        DocumentInspectorFactoryInterface $inspectorFactory,
        ProxyFactory $proxyFactory,
        SessionInterface $phpcrSession
    ) {
        $this->metadataFactory = $metadataFactory;
        $this->nodeManager = $nodeManager;
        $this->documentRegistry = $documentRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->documentManager = $documentManager;
        $this->inspectorFactory = $inspectorFactory;
        $this->proxyFactory = $proxyFactory;
        $this->phpcrSession = $phpcrSession;
        $proxyFactory->attachContext($this);
    }

    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    public function getNodeManager()
    {
        return $this->nodeManager;
    }

    public function getRegistry()
    {
        return $this->documentRegistry;
    }

    public function getProxyFactory()
    {
        return $this->proxyFactory;
    }

    public function getDocumentManager()
    {
        return $this->documentManager;
    }

    public function getInspector()
    {
        return $this->inspectorFactory->getInspector($this);
    }

    public function getPhpcrSession()
    {
        return $this->phpcrSession;
    }
}
