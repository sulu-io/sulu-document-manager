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
use Symfony\Component\EventDispatcher\Event;

/** 
 * Abstract class for events which require the DocumentManagerContext - i.e.
 * all events for which subscribers require access to a document manager or any
 * of its dependencies.
 */
abstract class AbstractDocumentManagerContextEvent extends AbstractEvent
{
    private $context;

    /**
     * @param DocumentManagerContext $context
     */
    public function __construct(DocumentManagerContext $context)
    {
        $this->context = $context;
    }

    public function getContext()
    {
        if (null === $this->context) {
            throw new \RuntimeException(
                'No DocumentManagerContext has been set on this event, maybe this class has overridden the constructor and forgotten about it?'
            );
        }

        return $this->context;
    }

    /**
     * @return string
     */
    public function getDebugMessage()
    {
        return '';
    }
}
