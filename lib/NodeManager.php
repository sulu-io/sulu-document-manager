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

use PHPCR\ItemNotFoundException;
use PHPCR\NodeInterface;
use PHPCR\RepositoryException;
use PHPCR\SessionInterface;
use PHPCR\Util\NodeHelper;
use PHPCR\Util\UUIDHelper;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;

/**
 * The node manager is responsible for talking to the PHPCR implementation.
 */
class NodeManager
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Find a document with the given path or UUID.
     *
     * @param string $identifier UUID or path
     *
     * @return NodeInterface
     *
     * @throws DocumentNotFoundException
     */
    public function find($identifier)
    {
        $exceptionMessage = sprintf('Could not find document with ID or path "%s"', $identifier);

        // try to return the node, if the identifier looks like an ID, get by
        // identifier otherwise by path.
        //
        // the getNodeByIdentifier() method will throw a RepositoryException,
        // the getNode() method a ItemNotFoundException. We catch both and
        // return a DocumentNotFoundException (wrapping the original
        // exception).
        try {
            if (UUIDHelper::isUUID($identifier)) {
                return $this->session->getNodeByIdentifier($identifier);
            }

            return $this->session->getNode($identifier);
        } catch (RepositoryException $e) {
            throw new DocumentNotFoundException($exceptionMessage, null, $e);
        } catch (ItemNotFoundException $e) {
            throw new DocumentNotFoundException($exceptionMessage, null, $e);
        }
    }

    /**
     * Determine if a node exists at the specified path or if a UUID is given,
     * then if a node with the UUID exists.
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function has($identifier)
    {
        try {
            $this->find($identifier);

            return true;
        } catch (DocumentNotFoundException $e) {
            return false;
        }
    }

    /**
     * Remove the document with the given path or UUID.
     *
     * @param string $identifier ID or path
     */
    public function remove($identifier)
    {
        $identifier = $this->normalizeToPath($identifier);
        $this->session->removeItem($identifier);
    }

    /**
     * Move the document with the given path or ID to the path
     * of the destination document (as a child).
     *
     * @param string $srcId
     * @param string $destId
     * @param string $name
     */
    public function move($srcId, $destId, $name)
    {
        $srcPath = $this->normalizeToPath($srcId);
        $parentDestPath = $this->normalizeToPath($destId);
        $destPath = $parentDestPath . '/' . $name;

        $this->session->move($srcPath, $destPath);
    }

    /**
     * Copy the document with the given path or ID to the path
     * of the destination document (as a child).
     *
     * @param string $srcId
     * @param string $destId
     * @param string $name
     *
     * @return string
     */
    public function copy($srcId, $destId, $name)
    {
        $workspace = $this->session->getWorkspace();
        $srcPath = $this->normalizeToPath($srcId);
        $parentDestPath = $this->normalizeToPath($destId);
        $destPath = $parentDestPath . '/' . $name;

        $workspace->copy($srcPath, $destPath);

        return $destPath;
    }

    /**
     * Save all pending changes currently recorded in this Session.
     */
    public function save()
    {
        $this->session->save();
    }

    /**
     * Clear the current session.
     */
    public function clear()
    {
        $this->session->refresh(false);
    }

    /**
     * Create a path.
     *
     * All nodes created are given a UUID and the accompanying
     * `mix:referenceable` mixin.
     *
     * The user may specify a custom UUID using the second argument.
     *
     * @param string $path
     * @param string|null $uuid
     *
     * @return NodeInterface
     */
    public function createPath($path, $uuid = null)
    {
        $current = $this->session->getRootNode();

        $segments = preg_split('#/#', $path, null, PREG_SPLIT_NO_EMPTY);
        foreach ($segments as $segment) {
            if ($current->hasNode($segment)) {
                $current = $current->getNode($segment);
            } else {
                $current = $current->addNode($segment);

                $current->addMixin('mix:referenceable');
                $current->setProperty('jcr:uuid', $uuid ?: UUIDHelper::generateUUID());
            }
        }

        return $current;
    }

    /**
     * Purge the workspace.
     */
    public function purgeWorkspace()
    {
        NodeHelper::purgeWorkspace($this->session);
    }

    /**
     * Normalize the given path or ID to a path.
     *
     * @param string $identifier
     *
     * @return string
     */
    private function normalizeToPath($identifier)
    {
        if (UUIDHelper::isUUID($identifier)) {
            $identifier = $this->session->getNodeByIdentifier($identifier)->getPath();
        }

        return $identifier;
    }
}
