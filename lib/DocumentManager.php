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
use ProxyManager\Factory\LazyLoadingGhostFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentManager implements DocumentManagerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array Cached options resolver instances
     */
    private $optionsResolvers = [];

    /**
     * @var DocumentInspectorFactory
     */
    private $inspectorFactory;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var DocumentRegistry
     */
    private $registry;

    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        SessionInterface $session,
        EventDispatcherInterface $eventDispatcher,
        MetadataFactoryInterface $metadataFactory,
        LazyLoadingGhostFactory $lazyProxyFactory,
        DocumentInspectorFactoryInterface $inspectorFactory,
        $defaultLocale
    ) {
        $this->eventDispatcher = $eventDispatcher;

        $this->nodeManager = new NodeManager($session);
        $this->registry = new DocumentRegistry($defaultLocale);
        $this->inspectorFactory = $inspectorFactory;
        $this->proxyFactory = new ProxyFactory($this, $lazyProxyFactory, $metadataFactory);
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function find($identifier, $locale = null, array $options = [])
    {
        $options = $this->getOptionsResolver(Events::FIND)->resolve($options);

        $event = new Event\FindEvent($this, $identifier, $locale, $options);
        $this->eventDispatcher->dispatch(Events::FIND, $event);

        return $event->getDocument();
    }

    /**
     * {@inheritdoc}
     */
    public function create($alias)
    {
        $event = new Event\CreateEvent($this, $alias);
        $this->eventDispatcher->dispatch(Events::CREATE, $event);

        return $event->getDocument();
    }

    /**
     * {@inheritdoc}
     */
    public function persist($document, $locale = null, array $options = [])
    {
        $options = $this->getOptionsResolver(Events::FIND)->resolve($options);

        $event = new Event\PersistEvent($this, $document, $locale, $options);
        $this->eventDispatcher->dispatch(Events::PERSIST, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($document)
    {
        $event = new Event\RemoveEvent($this, $document);
        $this->eventDispatcher->dispatch(Events::REMOVE, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function move($document, $destId)
    {
        $event = new Event\MoveEvent($this, $document, $destId);
        $this->eventDispatcher->dispatch(Events::MOVE, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($document, $destPath)
    {
        $event = new Event\CopyEvent($this, $document, $destPath);
        $this->eventDispatcher->dispatch(Events::COPY, $event);

        return $event->getCopiedPath();
    }

    /**
     * {@inheritdoc}
     */
    public function reorder($document, $destId, $after = false)
    {
        $event = new Event\ReorderEvent($this, $document, $destId, $after);
        $this->eventDispatcher->dispatch(Events::REORDER, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($document)
    {
        $event = new Event\RefreshEvent($this, $document);
        $this->eventDispatcher->dispatch(Events::REFRESH, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $event = new Event\FlushEvent($this);
        $this->eventDispatcher->dispatch(Events::FLUSH, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $event = new Event\ClearEvent($this, $this);
        $this->eventDispatcher->dispatch(Events::CLEAR, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery($query, $locale = null, array $options = [])
    {
        $event = new Event\QueryCreateEvent($this, $query, $locale, $options);
        $this->eventDispatcher->dispatch(Events::QUERY_CREATE, $event);

        return $event->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder()
    {
        $event = new Event\QueryCreateBuilderEvent($this);
        $this->eventDispatcher->dispatch(Events::QUERY_CREATE_BUILDER, $event);

        return $event->getQueryBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getInspector()
    {
        return $this->inspectorFactory->getInspector($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeManager()
    {
        return $this->nodeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getRegistry()
    {
        return $this->registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getProxyFactory()
    {
        return $this->proxyFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * {@inheritdoc}
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    private function getOptionsResolver($eventName)
    {
        if (isset($this->optionsResolvers[$eventName])) {
            return $this->optionsResolvers[$eventName];
        }

        $resolver = new OptionsResolver();
        $resolver->setDefault('locale', null);

        $event = new Event\ConfigureOptionsEvent($resolver);
        $this->eventDispatcher->dispatch(Events::CONFIGURE_OPTIONS, $event);

        $this->optionsResolvers[$eventName] = $resolver;

        return $resolver;
    }

    public function getMetadataFactory() 
    {
        return $this->metadataFactory;
    }
    
}
