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
use Sulu\Component\DocumentManager\DocumentManagerContext;
use Symfony\Component\EventDispatcher\Event;

class ConfigureOptionsEvent extends Event
{
    use EventOptionsTrait;

    /**
     * @param OptionsResolver $options
     */
    public function __construct(OptionsResolver $options)
    {
        $this->options = $options;
    }
}
