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

use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\DocumentManager\DocumentManagerContext;

abstract class AbstractEvent extends Event
{
    private $context;

    public function attachContext(DocumentManagerContext $context)
    {
        $this->context = $context;
    }

    final public function getContext()
    {
        if (null === $this->context) {
            throw new \RuntimeException(
                'No context has been attached to this event. Every event originating from a document manager should have a context.'
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
