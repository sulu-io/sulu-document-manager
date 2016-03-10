<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Phpcr;

use PHPCR\NodeInterface;
use PHPCR\Util\PathHelper;
use PHPCR\Util\UUIDHelper;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\DocumentManager\NodeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles the document reorder operation.
 */
class ReorderSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::REORDER => ['handleReorder', 500],
        ];
    }

    /**
     * Handle the reorder operation.
     *
     * @param ReorderEvent $event
     *
     * @throws DocumentManagerException
     */
    public function handleReorder(ReorderEvent $event)
    {
        $document = $event->getDocument();
        $siblingId = $event->getDestId();
        $after = $event->getAfter();
        $manager = $event->getManager();

        $node = $manager->getRegistry()->getNodeForDocument($document);
        $parentNode = $node->getParent();

        $nodeName = $node->getName();
        $siblingName = $this->resolveSiblingName($manager->getNodeManager(), $siblingId, $parentNode, $node);
        if (true === $after) {
            $siblingName = $this->resolveAfterSiblingName($parentNode, $siblingName);
        }

        $parentNode->orderBefore($nodeName, $siblingName);
    }

    private function resolveSiblingName(NodeManager $nodeManager, $siblingId, NodeInterface $parentNode, NodeInterface $node)
    {
        if (null === $siblingId) {
            return;
        }

        $siblingPath = $siblingId;
        if (UUIDHelper::isUUID($siblingId)) {
            $siblingPath = $nodeManager->find($siblingId)->getPath();
        }

        if ($siblingPath !== null && PathHelper::getParentPath($siblingPath) !== $parentNode->getPath()) {
            throw new DocumentManagerException(sprintf(
                'Cannot reorder documents which are not siblings. Trying to reorder "%s" to "%s"',
                $node->getPath(), $siblingPath
            ));
        }

        if (null !== $siblingPath) {
            return PathHelper::getNodeName($siblingPath);
        }

        return $node->getName();
    }

    /**
     * If the node should be ordered after the target then we need to order the
     * node before the sibling after the target sibling. If the node should be the
     * last sibling, then the target sibling should be NULL.
     *
     * @param NodeInterface $parentNode
     * @param string $siblingName
     *
     * @return string
     */
    private function resolveAfterSiblingName(NodeInterface $parentNode, $siblingName)
    {
        $targetName = null;
        $found = false;

        foreach (array_keys($parentNode->getNodes()) as $name) {
            if ($name === $siblingName) {
                $found = true;
                continue;
            } elseif ($found) {
                $targetName = $name;
                break;
            }
        }

        return $targetName;
    }
}
