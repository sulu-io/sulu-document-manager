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

use PHPCR\NodeInterface;

class RestoreEvent extends AbstractMappingEvent
{
    /**
     * @var string
     */
    private $version;

    /**
     * @param object $document
     * @param string $locale
     */
    public function __construct($document, $locale, $version)
    {
        $this->document = $document;
        $this->locale = $locale;
        $this->version = $version;
    }

    /**
     * Sets the node this event should operate on.
     *
     * @param NodeInterface $node
     */
    public function setNode(NodeInterface $node)
    {
        $this->node = $node;
    }

    /**
     * Returns the version, which should be restored.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
