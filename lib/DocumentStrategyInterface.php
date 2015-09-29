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

/**
 * Document strategies determine how documents are managed.
 */
interface DocumentStrategyInterface
{
    /**
     * Create a new node for the given document.
     *
     * The strategy should add the node to the parent document and set
     * any necessary properties for that the node can be managed.
     *
     * Note that strategies MUST ensure that the node is referenceable and SHOULD
     * set the UUID preemptively.
     *
     * @param object $document
     * @param NodeInterface $parentNode
     * @param string $name
     *
     * @return NodeInterface
     */
    public function createNodeForDocument($document, NodeInterface $parentNode, $name);

    /**
     * Return the Metadata object for the given node.
     *
     * The strategy should use the MetadataFactory and the properties set previously
     * in createNodeForDocument to determine the PHPCR type of the node's document.
     *
     * If no Metadata can be determined then the method should return NULL.
     *
     * @param NodeInterface $node
     *
     * @return Metadata|null
     */
    public function resolveMetadataForNode(NodeInterface $node);
}
