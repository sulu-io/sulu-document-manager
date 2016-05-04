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

class PublishEvent extends AbstractMappingEvent
{
    /**
     * @param object $document
     * @param string $locale
     */
    public function __construct($document, $locale)
    {
        $this->document = $document;
        $this->locale = $locale;
    }
}
