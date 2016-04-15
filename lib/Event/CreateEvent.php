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
use Sulu\Component\DocumentManager\Exception\RuntimeException;
use Sulu\Component\DocumentManager\DocumentManagerContext;

class CreateEvent extends AbstractDocumentManagerContextEvent
{
    /**
     * @var object
     */
    private $document;

    /**
     * @var string
     */
    private $alias;

    /**
     * @param DocumentManagerContext $manager
     * @param string $alias
     */
    public function __construct(DocumentManagerContext $manager, $alias)
    {
        parent::__construct($manager);
        $this->alias = $alias;
    }

    /**
     * @return object
     *
     * @throws \RuntimeException
     */
    public function getDocument()
    {
        if (!$this->document) {
            throw new RuntimeException(
                'No document has been set, an event listener should have created a document before ' .
                'this method was called.'
            );
        }

        return $this->document;
    }

    /**
     * @param object $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }
}
