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

use Sulu\Component\DocumentManager\Query\Query;

interface DocumentManagerInterface
{
    /**
     * Find a document by path or UUID in the given locale, optionally enforcing the given type.
     *
     * @param string $identifier Path or UUID
     * @param string $locale Locale
     * @param array $options
     *
     * @throws Exception\DocumentManagerException
     *
     * @return object
     */
    public function find($identifier, $locale = null, array $options = []);

    /**
     * Create a new document instance for the given alias.
     *
     * @param string
     *
     * @throws Exception\MetadataNotFoundException
     *
     * @return object
     */
    public function create($alias);

    /**
     * Persist a document to a PHPCR node.
     *
     * @param object $document
     * @param string $locale
     * @param array $options
     */
    public function persist($document, $locale = null, array $options = []);

    /**
     * Remove the document. The document should be unregistered and the related PHPCR node should be removed from the
     * session.
     *
     * @param object $document
     */
    public function remove($document);

    /**
     * Move the PHPCR node to which the document is mapped to be a child of the node at the given path or UUID.
     *
     * @param object $document
     * @param string $destId The path of the new parent
     */
    public function move($document, $destId);

    /**
     * Create a copy of the node representing the given document at the given path.
     *
     * @param object $document
     * @param string $destPath
     *
     * @return string
     */
    public function copy($document, $destPath);

    /**
     * Re-Order node before or after a specific node.
     *
     * @param object $document
     * @param string $destId
     */
    public function reorder($document, $destId);

    /**
     * Publishes a document to the public workspace.
     *
     * @param object $document
     * @param string $locale
     * @param array $options
     */
    public function publish($document, $locale, array $options = []);

    /**
     * Unpublishes a document from the public workspace.
     *
     * @param object $document
     * @param string $locale
     */
    public function unpublish($document, $locale);

    /**
     * Removes the draft for the given document and reverts it to the values from the public workspace.
     *
     * @param $document
     * @param $locale
     */
    public function removeDraft($document, $locale);

    /**
     * Restores the given version of the document and makes it a new version keeping the linear approach.
     *
     * @param object $document
     * @param string $locale
     * @param string $version The UUID of the version to restore
     * @param array $options
     *
     * @throws Exception\VersionNotFoundException
     *
     * @return mixed
     */
    public function restore($document, $locale, $version, array $options = []);

    /**
     * Refresh the given document with the persisted state of the node.
     *
     * @param object $document
     */
    public function refresh($document);

    /**
     * Persist changes to the persistent storage.
     */
    public function flush();

    /**
     * Clear the document manager, should reset the underlying PHPCR session and deregister all documents.
     */
    public function clear();

    /**
     * Create a new query from a JCR-SQL2 query string.
     *
     * NOTE: This should not be used generally as it exposes the database structure and breaks abstraction. Use the
     *       domain-aware query builder instead.
     *
     * @param mixed $query Either a JCR-SQL2 string, or a PHPCR query object
     * @param string $locale
     * @param array $options
     *
     * @return Query
     */
    public function createQuery($query, $locale = null, array $options = []);

    /**
     * Create a new query builder.
     *
     * By default this will return the PHPCR-ODM query builder.
     *
     * http://doctrine-phpcr-odm.readthedocs.org/en/latest/reference/query-builder.html
     */
    public function createQueryBuilder();
}
