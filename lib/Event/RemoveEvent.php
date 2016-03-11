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

class RemoveEvent extends AbstractDocumentEvent
{
    use EventOptionsTrait;

    public function __construct($document, array $options = [])
    {
        parent::__construct($document);

        $this->options = $options;
    }
}
