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
    private $defaultSession;

    /**
     * @param SessionInterface $defaultSession
     */
    public function __construct(SessionInterface $defaultSession)
    {
        $this->defaultSession = $defaultSession;
    }

    /**
     * Find a document with the given path or UUID.
     *
     * @param string $identifier UUID or path
     * @param SessionInterface $session
     *
     * @return NodeInterface
     *
     * @throws DocumentNotFoundException
     */
    public function find($identifier, $session = null)
    {
        if (null === $session) {
            $session = $this->defaultSession;
            $this->triggerSessionDeprecatedMessage();
        }

        try {
            if (UUIDHelper::isUUID($identifier)) {
                return $session->getNodeByIdentifier($identifier);
            }

            return $session->getNode($identifier);
        } catch (RepositoryException $e) {
            throw new DocumentNotFoundException(sprintf(
                'Could not find document with ID or path "%s"', $identifier
            ), null, $e);
        }
    }

    /**
     * Determine if a node exists at the specified path or if a UUID is given,
     * then if a node with the UUID exists.
     *
     * @param string $identifier
     * @param SessionInterface $session
     *
     * @return bool
     */
    public function has($identifier, $session = null)
    {
        if (null === $session) {
            $session = $this->defaultSession;
            $this->triggerSessionDeprecatedMessage();
        }

        $this->normalizeToPath($identifier, $session);

        try {
            $this->find($identifier, $session);

            return true;
        } catch (DocumentNotFoundException $e) {
            return false;
        }
    }

    /**
     * Remove the document with the given path or UUID.
     *
     * @param string $identifier ID or path
     * @param SessionInterface $session Session name
     */
    public function remove($identifier, $session = null)
    {
        if (null === $session) {
            $session = $this->defaultSession;
            $this->triggerSessionDeprecatedMessage();
        }

        $identifier = $this->normalizeToPath($identifier, $session);
        $session->removeItem($identifier);
    }

    /**
     * Move the document with the given path or ID to the path
     * of the destination document (as a child).
     *
     * @param string $srcId
     * @param string $destId
     * @param string $name
     * @param SessionInterface $session
     *
     * @deprecated Use NodeHelper::move instead
     */
    public function move($srcId, $destId, $name, $session = null)
    {
        if (null === $session) {
            $session = $this->defaultSession;
            $this->triggerSessionDeprecatedMessage();
        }

        $srcPath = $this->normalizeToPath($srcId, $session);
        $parentDestPath = $this->normalizeToPath($destId, $session);
        $destPath = $parentDestPath . '/' . $name;

        $session->move($srcPath, $destPath);
    }

    /**
     * Copy the document with the given path or ID to the path
     * of the destination document (as a child).
     *
     * @param string $srcId
     * @param string $destId
     * @param string $name
     * @param SessionInterface $session
     *
     * @return string
     *
     * @deprecated Use NodeHelper::copy instead
     */
    public function copy($srcId, $destId, $name, $session = null)
    {
        if (null === $session) {
            $session = $this->defaultSession;
            $this->triggerSessionDeprecatedMessage();
        }

        $workspace = $session->getWorkspace();
        $srcPath = $this->normalizeToPath($srcId, $session);
        $parentDestPath = $this->normalizeToPath($destId, $session);
        $destPath = $parentDestPath . '/' . $name;

        $workspace->copy($srcPath, $destPath);

        return $destPath;
    }

    /**
     * Save all pending changes currently recorded in this Session.
     *
     * @param SessionInterface $session
     */
    public function save($session = null)
    {
        if (null === $session) {
            $session = $this->defaultSession;
            $this->triggerSessionDeprecatedMessage();
        }

        $session->save();
    }

    /**
     * Clear the current session.
     *
     * @param SessionInterface $session
     */
    public function clear($session = null)
    {
        if (null === $session) {
            $session = $this->defaultSession;
            $this->triggerSessionDeprecatedMessage();
        }

        $session->refresh(false);
    }

    /**
     * Create a path.
     *
     * @param string $path
     * @param SessionInterface $session
     *
     * @return NodeInterface
     */
    public function createPath($path, $session = null)
    {
        if (null === $session) {
            $this->triggerSessionDeprecatedMessage();
        }

        $current = $session->getRootNode();

        $segments = preg_split('#/#', $path, null, PREG_SPLIT_NO_EMPTY);
        foreach ($segments as $segment) {
            if ($current->hasNode($segment)) {
                $current = $current->getNode($segment);
            } else {
                $current = $current->addNode($segment);
                $current->addMixin('mix:referenceable');
                $current->setProperty('jcr:uuid', UUIDHelper::generateUUID());
            }
        }

        return $current;
    }

    /**
     * Purge the workspace.
     *
     * @param SessionInterface $session
     */
    public function purgeWorkspace($session = null)
    {
        if (null === $session) {
            $session = $this->defaultSession;
            $this->triggerSessionDeprecatedMessage();
        }

        NodeHelper::purgeWorkspace($session);
    }

    /**
     * Normalize the given path or ID to a path.
     *
     * @param string $identifier
     * @param SessionInterface $session
     *
     * @return string
     */
    private function normalizeToPath($identifier, $session)
    {
        if (UUIDHelper::isUUID($identifier)) {
            $identifier = $session->getNodeByIdentifier($identifier)->getPath();
        }

        return $identifier;
    }

    /**
     * Trigger session deprecated message.
     */
    private function triggerSessionDeprecatedMessage()
    {
        trigger_error('Calling the "NodeManager" without giving the session is deprecated!');
    }
}
