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

use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\DocumentManagerContext;
use Symfony\Component\EventDispatcher\Event;

class MetadataLoadEvent extends Event
{
    /**
     * @param NodeInterface $node
     * @param string $locale
     * @param array $options
     */
    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * @return Metadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}
