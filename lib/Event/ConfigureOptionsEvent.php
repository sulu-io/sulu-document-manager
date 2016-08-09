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

use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigureOptionsEvent extends AbstractEvent
{
    use EventOptionsTrait;

    /**
     * @var string
     */
    private $eventName;

    /**
     * @param OptionsResolver $options
     * @param string $eventName
     */
    public function __construct(OptionsResolver $options, $eventName)
    {
        $this->options = $options;
        $this->eventName = $eventName;
    }

    /**
     * Returns event to configure options.
     *
     * @return string
     */
    public function getEventName()
    {
        return $this->eventName;
    }
}
