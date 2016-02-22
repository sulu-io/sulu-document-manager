<?php

namespace Sulu\Component\DocumentManager;

use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    public function __construct(
        MetadataFactoryInterface $metadataFactory,
        NodeManager $nodeManager,
        DocumentRegistry $documentRegistry,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->metadataFactory = $metadataFactory;
        $this->nodeManager = $nodeManager;
        $this->documentRegistry = $documentRegistry;
        $this->eventDispatcher = $eventDispatcher;
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
        return $this->registry;
    }

    public function getProxyFactory()
    {
        return $this->proxyFactory;
    }
}
