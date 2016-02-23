<?php

namespace Sulu\Component\DocumentManager;

use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Component\DocumentManager\ProxyFactory;

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

    public function __construct(
        DocumentManagerInterface $documentManager,
        MetadataFactoryInterface $metadataFactory,
        NodeManager $nodeManager,
        DocumentRegistry $documentRegistry,
        EventDispatcherInterface $eventDispatcher,
        DocumentInspectorFactoryInterface $inspectorFactory,
        ProxyFactory $proxyFactory
    )
    {
        $this->metadataFactory = $metadataFactory;
        $this->nodeManager = $nodeManager;
        $this->documentRegistry = $documentRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->documentManager = $documentManager;
        $this->inspectorFactory = $inspectorFactory;
        $this->proxyFactory = $proxyFactory;
        $proxyFactory->attachContext($this);
        $inspectorFactory->attachContext($this);
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
        return $this->inspectorFactory->getInspector();
    }
}
