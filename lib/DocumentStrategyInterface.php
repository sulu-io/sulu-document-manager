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
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;

/**
 * Document strategies determine how documents are managed, for example
 * if the document class should be determined by a mixin, or by the primary node type.
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

    /**
     * Return the primary node type from the given document class FQN.
     *
     * For example:
     *
     * ```
     * return 'nt:unstructured';
     * ```
     *
     * @param string $classFqn
     *
     * @return string
     */
    public function getPrimaryNodeType($classFqn);

    /**
     * Create a source constraint for a source document. That is return the
     * constraint that should be used to return only documents of the given
     * class FQN.
     *
     * For example:
     *
     * ```
     * return $qomf->comparison(
     *     $qomf->propertyValue(
     *         $sourceNode->getAlias(),
     *         'jcr:mixinTypes'
     *     ),
     *     QOMConstants::JCR_OPERATOR_EQUAL_TO,
     *     $qomf->literal('foo')
     * );
     * ```
     *
     * Can return NULL if no constraints should be added. This may be required
     * if the primary type already represents the document class.
     *
     * @param QueryObjectModelFactoryInterface $qomf
     * @param string $sourceAlias
     * @param string $classFqn
     *
     * @return \PHPCR\Query\QOM\ConstraintInterface
     */
    public function createSourceConstraint(QueryObjectModelFactoryInterface $qomf, $sourceAlias, $classFqn);
}
