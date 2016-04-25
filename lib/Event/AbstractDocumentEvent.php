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

abstract class AbstractDocumentEvent extends AbstractDocumentManagerContextEvent
{
    /**
     * @var object
     */
    private $document;

    /**
     * @param DocumentManagerContext $context
     * @param object $document
     */
    public function __construct(DocumentManagerContext $context, $document)
    {
        parent::__construct($context);
        $this->document = $document;
    }

    /**
     * @return object
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * {@inheritdoc}
     */
    public function getDebugMessage()
    {
        return sprintf(
            '%sd:%s (%s)',
            parent::getDebugMessage(),
            $this->document ? spl_object_hash($this->document) : '<no document>',
            substr(get_class($this->document), strrpos(get_class($this->document), '\\') + 1)
        );
    }
}
