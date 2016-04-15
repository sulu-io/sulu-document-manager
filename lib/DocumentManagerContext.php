<?php

namespace Sulu\Component\DocumentManager;

use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\ProxyFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use PHPCR\SessionInterface;

class DocumentManagerContext
{
    private $documentManager;
    private $nodeManager;
    private $documentRegistry;
    private $proxyFactory;
    private $eventDispatcher;
    private $metadataFactory;
    private $session;

    public function __construct(
        DocumentManagerInterface $documentManager,
        NodeManager $nodeManager,
        DocumentRegistry $documentRegistry,
        ProxyFactory $proxyFactory,
        EventDispatcher $eventDispatcher,
        MetadataFactoryInterface $metadataFactory,
        SessionInterface $session
    )
    {
        $this->documentManager = $documentManager;
        $this->nodeManager = $nodeManager;
        $this->documentRegistry = $documentRegistry;
        $this->proxyFactory = $proxyFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->session = $session;
        $this->metadataFactory = $metadataFactory;
    }

    public function getNodeManager() 
    {
        return $this->nodeManager;
    }

    public function getDocumentRegistry() 
    {
        return $this->documentRegistry;
    }

    public function getProxyFactory() 
    {
        return $this->proxyFactory;
    }

    public function getEventDispatcher() 
    {
        return $this->eventDispatcher;
    }

    public function getMetadataFactory() 
    {
        return $this->metadataFactory;
    }

    public function getDocumentManager()
    {
        return $this->documentManager;
    }

    public function getSession() 
    {
        return $this->session;
    }
    
}
