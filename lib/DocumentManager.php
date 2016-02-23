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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\AbstractEvent;
use Sulu\Component\DocumentManager\ProxyFactory;

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

    private $context;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        NodeManager $nodeManager,
        MetadataFactoryInterface $metadataFactory,
        DocumentRegistry $documentRegistry,
        DocumentInspector $documentInspector,
        ProxyFactory $proxyFactory
    )
    {
        $this->eventDispatcher = $eventDispatcher;

        $this->context = new DocumentManagerContext(
            $this,
            $metadataFactory,
            $nodeManager,
            $documentRegistry,
            $eventDispatcher,
            $documentInspector,
            $proxyFactory
        );
    }

    /**
     * {@inheritdoc}
     */
    public function find($identifier, $locale = null, array $options = [])
    {
        $options = $this->getOptionsResolver(Events::FIND)->resolve($options);

        $event = new Event\FindEvent($identifier, $locale, $options);
        $this->dispatch(Events::FIND, $event);

        return $event->getDocument();
    }

    /**
     * {@inheritdoc}
     */
    public function create($alias)
    {
        $event = new Event\CreateEvent($alias);
        $this->dispatch(Events::CREATE, $event);

        return $event->getDocument();
    }

    /**
     * {@inheritdoc}
     */
    public function persist($document, $locale = null, array $options = [])
    {
        $options = $this->getOptionsResolver(Events::FIND)->resolve($options);

        $event = new Event\PersistEvent($document, $locale, $options);
        $this->dispatch(Events::PERSIST, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($document)
    {
        $event = new Event\RemoveEvent($document);
        $this->dispatch(Events::REMOVE, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function move($document, $destId)
    {
        $event = new Event\MoveEvent($document, $destId);
        $this->dispatch(Events::MOVE, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($document, $destPath)
    {
        $event = new Event\CopyEvent($document, $destPath);
        $this->dispatch(Events::COPY, $event);

        return $event->getCopiedPath();
    }

    /**
     * {@inheritdoc}
     */
    public function reorder($document, $destId, $after = false)
    {
        $event = new Event\ReorderEvent($document, $destId, $after);
        $this->dispatch(Events::REORDER, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($document)
    {
        $event = new Event\RefreshEvent($document);
        $this->dispatch(Events::REFRESH, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $event = new Event\FlushEvent($this->context);
        $this->dispatch(Events::FLUSH, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $event = new Event\ClearEvent($this->context);
        $this->dispatch(Events::CLEAR, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery($query, $locale = null, array $options = [])
    {
        $event = new Event\QueryCreateEvent($query, $locale, $options);
        $this->dispatch(Events::QUERY_CREATE, $event);

        return $event->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder()
    {
        $event = new Event\QueryCreateBuilderEvent($this->context);
        $this->dispatch(Events::QUERY_CREATE_BUILDER, $event);

        return $event->getQueryBuilder();
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
        $this->dispatch(Events::CONFIGURE_OPTIONS, $event);

        $this->optionsResolvers[$eventName] = $resolver;

        return $resolver;
    }

    private function dispatch($eventName, AbstractEvent $event)
    {
        $event->attachContext($this->context);
        $this->eventDispatcher->dispatch($eventName, $event);
    }
}
