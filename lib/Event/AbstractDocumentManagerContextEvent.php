<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

use Sulu\Component\DocumentManager\DocumentManagerContext;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Abstract class for events which require the DocumentManagerInterface - i.e.
 * all events for which subscribers require access to a document context or any
 * of its dependencies.
 *
 * TODO: The methods here are proxies to the context. We should either:
 *
 *       a) Remove these proxy methods.
 *       b) Add an interface, e.g. DocumentManagerContextAccessorInterface,
 *          (but we should first create a DocumentManager namespace and move the
 *          DocumentManager* classes there).
 *
 *          see: https://github.com/sulu/sulu-document-manager/issues/77
 */
abstract class AbstractDocumentManagerContextEvent extends AbstractEvent
{
    private $context;

    /**
     * @param DocumentManagerInterface $context
     */
    public function __construct(DocumentManagerContext $context)
    {
        $this->context = $context;
    }

    public function getManager()
    {
        return $this->getContext()->getManager();
    }

    public function getNodeManager()
    {
        return $this->getContext()->getNodeManager();
    }

    public function getRegistry()
    {
        return $this->getContext()->getRegistry();
    }

    public function getProxyFactory()
    {
        return $this->getContext()->getProxyFactory();
    }

    public function getEventDispatcher()
    {
        return $this->getContext()->getEventDispatcher();
    }

    public function getMetadataFactory()
    {
        return $this->getContext()->getMetadataFactory();
    }

    public function getSession()
    {
        return $this->getContext()->getSession();
    }

    /**
     * @return string
     */
    public function getDebugMessage()
    {
        return $this->getContext()->getName();
    }

    public function getContext()
    {
        if (null === $this->context) {
            throw new \RuntimeException(
                'No DocumentManagerContext instance has been set on this event, maybe this class has overridden the constructor and forgotten about it?'
            );
        }

        return $this->context;
    }
}
