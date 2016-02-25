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
use Sulu\Component\DocumentManager\DocumentManagerContext;

class ReorderEvent extends AbstractMappingEvent
{
    /**
     * @var string
     */
    private $destId;

    /**
     * @var bool
     */
    private $after;

    /**
     * @param object $document
     * @param string $destId
     * @param bool $after
     */
    public function __construct(DocumentManagerContext $context, $document, $destId, $after)
    {
        parent::__construct($context);
        $this->document = $document;
        $this->destId = $destId;
        $this->after = $after;
    }

    /**
     * {@inheritdoc}
     */
    public function getDebugMessage()
    {
        return sprintf(
            '%s did:%s after:%s',
            parent::getDebugMessage(),
            $this->destId ?: '<no dest>',
            $this->after ? 'true' : 'false'
        );
    }

    /**
     * @return string
     */
    public function getDestId()
    {
        return $this->destId;
    }

    /**
     * @return bool
     */
    public function getAfter()
    {
        return $this->after;
    }

    /**
     * @param NodeInterface $node
     */
    public function setNode(NodeInterface $node)
    {
        $this->node = $node;
    }
}
