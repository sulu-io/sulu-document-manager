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

use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\DocumentManager\DocumentManagerContext;

/**
 * Abstract class for events which require the DocumentManagerInterface - i.e.
 * all events for which subscribers require access to a document context or any
 * of its dependencies.
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

    public function getDocumentManager()
    {
        return $this->getContext()->getDocumentManager();
    }

    public function getNodeManager()
    {
        return $this->getContext()->getNodeManager();
    }

    public function getDocumentRegistry()
    {
        return $this->getContext()->getDocumentRegistry();
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
        return '';
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
