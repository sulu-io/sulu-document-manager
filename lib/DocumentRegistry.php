<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

/**
 * Handles the mapping between managed documents and nodes.
 */
class DocumentRegistry
{
    /**
     * @var array
     */
    private $documentMap = [];

    /**
     * @var array
     */
    private $documentNodeMap = [];

    /**
     * @var array
     */
    private $nodeMap = [];

    /**
     * @var array
     */
    private $nodeDocumentMap = [];

    /**
     * @var array
     */
    private $documentLocaleMap = [];

    /**
     * @var array
     */
    private $originalLocaleMap = [];

    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * @var array
     */
    private $hydrationState = [];

    /**
     * @param $defaultLocale
     */
    public function __construct($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Register a document.
     *
     * @param mixed $document
     * @param NodeInterface $node
     * @param NodeInterface $node
     * @param null|string $locale
     *
     * @throws DocumentManagerException
     */
    public function registerDocument($document, NodeInterface $node, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->defaultLocale;
        }

        $oid = $this->getObjectIdentifier($document);
        $uuid = $node->getIdentifier();

        // do not allow nodes without UUIDs or reregistration of documents
        $this->validateDocumentRegistration($document, $node, $oid, $uuid);

        $this->documentMap[$oid] = $document;
        $this->documentNodeMap[$oid] = $uuid;
        $this->nodeMap[$node->getIdentifier()] = $node;
        $this->nodeDocumentMap[$node->getIdentifier()] = $document;
        $this->documentLocaleMap[$oid] = $locale;
    }

    /**
     * Update the locale of the given document and store the originally
     * requested locale.
     *
     * The originally requested locale should be reset when a HYDRATE event
     * is caused by the user (and not internally when loading dependencies).
     *
     * @param object $document
     * @param string $locale
     * @param null|string $originalLocale
     */
    public function updateLocale($document, $locale, $originalLocale = null)
    {
        $oid = $this->getObjectIdentifier($document);
        $this->originalLocaleMap[$oid] = $originalLocale;
        $this->documentLocaleMap[$oid] = $locale;
    }

    /**
     * Return true if the document is managed.
     *
     * @param object $document
     *
     * @return bool
     */
    public function hasDocument($document)
    {
        $oid = $this->getObjectIdentifier($document);

        return isset($this->documentMap[$oid]);
    }

    /**
     * Return true if the node is managed.
     *
     * @param NodeInterface $node
     *
     * @return bool
     */
    public function hasNode(NodeInterface $node, $locale = null)
    {
        if (null === $locale) {
            return isset($this->nodeDocumentMap[$node->getIdentifier()]);
        }

        $document = $this->getDocumentForNode($node);
        $oid = $this->getObjectIdentifier($document);

        return $this->documentLocaleMap[$oid] === $locale;
    }

    /**
     * Clear the registry (detach all documents).
     */
    public function clear()
    {
        $this->documentMap = [];
        $this->documentNodeMap = [];
        $this->nodeMap = [];
        $this->nodeDocumentMap = [];
        $this->documentLocaleMap = [];
        $this->originalLocaleMap = [];
        $this->hydrationState = [];
    }

    /**
     * Remove all references to the given document and its
     * associated node.
     *
     * @param object $document
     */
    public function deregisterDocument($document)
    {
        $oid = $this->getObjectIdentifier($document);

        $this->assertDocumentExists($document);

        $nodeIdentifier = $this->documentNodeMap[$oid];

        unset($this->nodeMap[$nodeIdentifier]);
        unset($this->nodeDocumentMap[$nodeIdentifier]);
        unset($this->documentMap[$oid]);
        unset($this->documentNodeMap[$oid]);
        unset($this->documentLocaleMap[$oid]);
        unset($this->originalLocaleMap[$oid]);
        unset($this->hydrationState[$oid]);
    }

    /**
     * Return the node for the given managed document.
     *
     * @param object $document
     *
     * @throws \RuntimeException If the node is not managed
     *
     * @return NodeInterface
     */
    public function getNodeForDocument($document)
    {
        $oid = $this->getObjectIdentifier($document);
        $this->assertDocumentExists($document);

        return $this->nodeMap[$this->documentNodeMap[$oid]];
    }

    /**
     * Return the current locale for the given document.
     *
     * @param object $document
     *
     * @return string
     */
    public function getLocaleForDocument($document)
    {
        $oid = $this->getObjectIdentifier($document);
        $this->assertDocumentExists($document);

        return $this->documentLocaleMap[$oid];
    }

    /**
     * Return the original locale for the document.
     *
     * @param object $document
     *
     * @return string
     */
    public function getOriginalLocaleForDocument($document)
    {
        $oid = $this->getObjectIdentifier($document);
        $this->assertDocumentExists($document);

        if (isset($this->originalLocaleMap[$oid])) {
            return $this->originalLocaleMap[$oid];
        }

        return $this->getLocaleForDocument($document);
    }

    /**
     * Return the document for the given managed node.
     *
     * @param NodeInterface $node
     *
     * @throws \RuntimeException If the node is not managed
     */
    public function getDocumentForNode(NodeInterface $node, $locale = null)
    {
        $identifier = $node->getIdentifier();
        $this->assertNodeExists($identifier);
        $document = $this->nodeDocumentMap[$identifier];
        $oid = $this->getObjectIdentifier($document);

        if ($locale !== null && $this->documentLocaleMap[$oid] !== $locale) {
            return;
        }

        return $document;
    }

    /**
     * Return the default locale.
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * @param object $document
     */
    private function assertDocumentExists($document)
    {
        $oid = spl_object_hash($document);

        if (!isset($this->documentMap[$oid])) {
            throw new \RuntimeException(sprintf(
                'Document "%s" with OID "%s" is not managed, there are "%s" managed objects,',
                get_class($document), $oid, count($this->documentMap)
            ));
        }
    }

    /**
     * @param mixed $identifier
     */
    private function assertNodeExists($identifier)
    {
        if (!isset($this->nodeDocumentMap[$identifier])) {
            throw new \RuntimeException(sprintf(
                'Node with identifier "%s" is not managed, there are "%s" managed objects,',
                $identifier, count($this->documentMap)
            ));
        }
    }

    /**
     * Get the spl object hash for the given object.
     *
     * @param object $document
     *
     * @return string
     */
    private function getObjectIdentifier($document)
    {
        return spl_object_hash($document);
    }

    /**
     * Ensure that the document is not already registered and that the node
     * has a UUID.
     *
     * @param object $document
     * @param NodeInterface $node
     * @param string $oid
     * @param string $uuid
     *
     * @throws DocumentManagerException
     */
    private function validateDocumentRegistration($document, NodeInterface $node, $oid, $uuid)
    {
        if (null === $uuid) {
            throw new DocumentManagerException(sprintf(
                'Node "%s" of type "%s" has no UUID. Only referencable nodes can be registered by the document manager',
                $node->getPath(), $node->getPrimaryNodeType()->getName()
            ));
        }

        if (isset($this->nodeMap[$uuid])) {
            $registeredDocument = $this->nodeDocumentMap[$uuid];
            throw new \RuntimeException(sprintf(
                'Document "%s" (%s) is already registered for node "%s" (%s) when trying to register document "%s" (%s)',
                spl_object_hash($registeredDocument),
                get_class($registeredDocument),
                $uuid,
                $node->getPath(),
                $oid,
                get_class($document)
            ));
        }
    }

    /**
     * Register that the document has been hydrated and that it should
     * not be hydrated again.
     *
     * @param object $document
     */
    public function markDocumentAsHydrated($document)
    {
        $oid = spl_object_hash($document);
        $this->hydrationState[$oid] = true;
    }

    /**
     * Unmark the document as being hydrated. It will then be
     * rehydrated the next time a HYDRATE event is fired for ot.
     *
     * @param object $document
     */
    public function unmarkDocumentAsHydrated($document)
    {
        $oid = spl_object_hash($document);
        unset($this->hydrationState[$oid]);
    }

    /**
     * Return true if the document is a candidate for hydration/re-hydration.
     *
     * @param object $document
     *
     * @return bool
     */
    public function isHydrated($document)
    {
        $oid = spl_object_hash($document);

        if (isset($this->hydrationState[$oid])) {
            return true;
        }

        return false;
    }
}
