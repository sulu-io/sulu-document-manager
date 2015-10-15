<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Exception;

use PHPCR\PropertyInterface;

/**
 * This is exception is thrown if a document is still referenced, and the executed action does not work because of that.
 */
class DocumentReferencedException extends DocumentManagerException
{
    /**
     * @var object
     */
    private $document;

    /**
     * @var PropertyInterface[]
     */
    private $references;

    /**
     * @param object $document
     * @param PropertyInterface[] $references
     * @param \Exception $previous
     */
    public function __construct($document, array $references = [], \Exception $previous = null)
    {
        $this->document = $document;
        $this->references = $references;

        parent::__construct(
            sprintf(
                'The document with the object hash "%s" cannot be deleted because it is still referenced',
                spl_object_hash($this->document)
            ),
            0,
            $previous
        );
    }

    /**
     * Returns the document which cannot be deleted because it is referenced.
     *
     * @return object
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Returns all the references causing the page not to be deletable.
     *
     * @return PropertyInterface[]
     */
    public function getReferences()
    {
        return $this->references;
    }
}
