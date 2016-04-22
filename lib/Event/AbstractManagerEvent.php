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
use Sulu\Component\DocumentManager\Exception\RuntimeException;

/**
 * Abstract class for events which require the DocumentManagerInterface - i.e.
 * all events for which subscribers require access to a document manager or any
 * of its dependencies.
 */
abstract class AbstractManagerEvent extends AbstractEvent
{
    private $manager;

    /**
     * @param DocumentManagerInterface $manager
     */
    public function __construct(DocumentManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function getManager()
    {
        if (null === $this->manager) {
            throw new RuntimeException(
                'No DocumentManagerInterface instance has been set on this event, maybe this class has overridden the constructor and forgotten about it?'
            );
        }

        return $this->manager;
    }

    /**
     * @return string
     */
    public function getDebugMessage()
    {
        $name = $this->manager->getName();

        if (null === $name) {
            return '';
        }

        return sprintf('[%s] ', $name);
    }
}
