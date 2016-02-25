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

abstract class AbstractEvent extends Event
{
    private $context;

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
