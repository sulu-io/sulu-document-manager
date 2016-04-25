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

use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A document manager has two roles:.
 *
 * - Provider of an API for working with documents.
 * - A "sub container", providing access to the services which fall
 *   within the scope of the particular document manager (as there can be many).
 *
 * This class is therefore an event dispatcher and a service container and has
 * no other business logic within it.
 *
 * All primary methods are wrapped in a try catch block, the exception is then
 * wrapped in a `DocumentManagerException` with a reference to this document
 * managers name.
 *
 * TODO: Create a proxy document manager which automatically wraps all calls.
 */
class DocumentManager implements DocumentManagerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var DocumentManagerContext
     */
    private $context;

    /**
     * The document manager context has a one-to-one relationship with the
     * document manager.
     *
     * @param DocumentManagerContext $context
     */
    public function __construct(DocumentManagerContext $context)
    {
        $this->context = $context;
        $this->eventDispatcher = $context->getEventDispatcher();
        $this->context->attachManager($this);
    }

    /**
     * Attach a name to this document manager (used to indicate the origin of
     * any exceptions thrown within this domain).
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function find($identifier, $locale = null, array $options = [])
    {
        try {
            $options = $this->getOptionsResolver(Events::FIND)->resolve($options);

            $event = new Event\FindEvent($this->context, $identifier, $locale, $options);
            $this->eventDispatcher->dispatch(Events::FIND, $event);

            return $event->getDocument();
        } catch (\Exception $e) {
            $this->processException($e, 'finding document');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create($alias)
    {
        try {
            $event = new Event\CreateEvent($this->context, $alias);
            $this->eventDispatcher->dispatch(Events::CREATE, $event);

            return $event->getDocument();
        } catch (\Exception $e) {
            $this->processException($e, 'creating document');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function persist($document, $locale = null, array $options = [])
    {
        try {
            $options = $this->getOptionsResolver(Events::FIND)->resolve($options);

            $event = new Event\PersistEvent($this->context, $document, $locale, $options);
            $this->eventDispatcher->dispatch(Events::PERSIST, $event);
        } catch (\Exception $e) {
            $this->processException($e, 'persisting document');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($document)
    {
        try {
            $event = new Event\RemoveEvent($this->context, $document);
            $this->eventDispatcher->dispatch(Events::REMOVE, $event);
        } catch (\Exception $e) {
            $this->processException($e, 'removing document');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function move($document, $destId)
    {
        try {
            $event = new Event\MoveEvent($this->context, $document, $destId);
            $this->eventDispatcher->dispatch(Events::MOVE, $event);
        } catch (\Exception $e) {
            $this->processException($e, 'moving document');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function copy($document, $destPath)
    {
        try {
            $event = new Event\CopyEvent($this->context, $document, $destPath);
            $this->eventDispatcher->dispatch(Events::COPY, $event);

            return $event->getCopiedPath();
        } catch (\Exception $e) {
            $this->processException($e, 'copying document');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reorder($document, $destId, $after = false)
    {
        try {
            $event = new Event\ReorderEvent($this->context, $document, $destId, $after);
            $this->eventDispatcher->dispatch(Events::REORDER, $event);
        } catch (\Exception $e) {
            $this->processException($e, 'reordering document');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($document)
    {
        try {
            $event = new Event\RefreshEvent($this->context, $document);
            $this->eventDispatcher->dispatch(Events::REFRESH, $event);
        } catch (\Exception $e) {
            $this->processException($e, 'refreshing document');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        try {
            $event = new Event\FlushEvent($this->context);
            $this->eventDispatcher->dispatch(Events::FLUSH, $event);
        } catch (\Exception $e) {
            $this->processException($e, 'flushing document manager');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        try {
            $event = new Event\ClearEvent($this->context);
            $this->eventDispatcher->dispatch(Events::CLEAR, $event);
        } catch (\Exception $e) {
            $this->processException($e, 'clearing document manager');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery($query, $locale = null, array $options = [])
    {
        try {
            $event = new Event\QueryCreateEvent($this->context, $query, $locale, $options);
            $this->eventDispatcher->dispatch(Events::QUERY_CREATE, $event);

            return $event->getQuery();
        } catch (\Exception $e) {
            $this->processException($e, 'creating query');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder()
    {
        try {
            $event = new Event\QueryCreateBuilderEvent($this->context);
            $this->eventDispatcher->dispatch(Events::QUERY_CREATE_BUILDER, $event);

            return $event->getQueryBuilder();
        } catch (\Exception $e) {
            $this->processException($e, 'creating query builder');
        }
    }

    /**
     * Return the document inspector.
     *
     * @return DocumentInspector
     */
    public function getInspector()
    {
        return $this->context->getInspector();
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

    /**
     * If the exception is an instanceof `DocumentManagerException` set the
     * document manager name that threw.
     *
     * @param \Exception $e
     * @param string $context
     */
    private function processException(\Exception $exception, $context)
    {
        $message = sprintf('Error %s', $context);

        if ($exception instanceof DocumentManagerException) {
            // if the exception already has a document manager name, then it
            // was thrown by this method in another document manager instance
            // so we wrap it again.
            //
            // the user-facing exception class will be lost and we are assuming that standard
            // exceptions (`DocumentNotFoundException`) will not be affected by this
            // within the scope of normal usage.
            if ($exception->getDocumentManagerName()) {
                throw new DocumentManagerException($message, $this->context->getName(), $exception);
            }

            $exception->setDocumentManagerName($this->context->getName());
            throw $exception;
        }

        throw $exception;
    }
}
