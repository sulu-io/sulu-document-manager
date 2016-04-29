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
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

class FindEvent extends AbstractDocumentManagerContextEvent
{
    use EventOptionsTrait;

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var object
     */
    private $document;

    /**
     * @param DocumentManagerContext $context
     * @param string $identifier
     * @param string $locale
     * @param array $options
     */
    public function __construct(DocumentManagerContext $context, $identifier, $locale, array $options = [])
    {
        parent::__construct($context);
        $this->identifier = $identifier;
        $this->locale = $locale;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getDebugMessage()
    {
        return sprintf(
            '%si:%s d:%s l:%s',
            parent::getDebugMessage(),
            $this->identifier,
            $this->document ? spl_object_hash($this->document) : '<no document>',
            $this->locale ?: '<no locale>'
        );
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return object
     *
     * @throws DocumentManagerException
     */
    public function getDocument()
    {
        if (!$this->document) {
            throw new DocumentManagerException(sprintf(
                'No document has been set for the findEvent for "%s". An event listener should have done this.',
                $this->identifier
            ));
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
}
