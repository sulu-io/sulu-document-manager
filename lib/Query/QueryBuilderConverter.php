<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Query;

use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode as QBConstants;
use Doctrine\ODM\PHPCR\Query\Builder\ConverterPhpcr;
use Doctrine\ODM\PHPCR\Query\Builder\OperandDynamicField;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Builder\SourceDocument;
use PHPCR\SessionInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Component\DocumentManager\DocumentStrategyInterface;

/**
 * Class which converts a Builder tree to a PHPCR Query.
 */
class QueryBuilderConverter extends ConverterPhpcr
{
    /**
     * j
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var Metadata[]
     */
    protected $documentMetadata = array();

    /**
     * @param PropertyEncoder
     */
    protected $encoder;

    /**
     * @param DocumentStrategyInterface
     */
    private $strategy;

    /**
     * @param SessionInterface $session
     * @param EventDispatcherInterface $eventDispatcher
     * @param MetadataFactoryInterface $metadataFactory
     * @param PropertyEncoder $encoder
     */
    public function __construct(
        SessionInterface $session,
        EventDispatcherInterface $eventDispatcher,
        MetadataFactoryInterface $metadataFactory,
        PropertyEncoder $encoder,
        DocumentStrategyInterface $strategy
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->qomf = $session->getWorkspace()->getQueryManager()->getQOMFactory();
        $this->metadataFactory = $metadataFactory;
        $this->encoder = $encoder;
        $this->strategy = $strategy;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuery(QueryBuilder $builder)
    {
        $this->documentMetadata = array();
        $this->sourceDocumentNodes = array();
        $this->constraint = null;

        $this->locale = $builder->getLocale();

        if (!$this->locale) {
            throw new \InvalidArgumentException(sprintf(
                'No locale specified'
            ));
        }

        $from = $builder->getChildrenOfType(
            QBConstants::NT_FROM
        );

        if (!$from) {
            throw new RuntimeException(
                'No From (source) node in query'
            );
        }

        $dispatches = array(
            QBConstants::NT_FROM,
            QBConstants::NT_SELECT,
            QBConstants::NT_WHERE,
            QBConstants::NT_ORDER_BY,
        );

        foreach ($dispatches as $dispatchType) {
            $this->dispatchMany($builder->getChildrenOfType($dispatchType));
        }

        if (count($this->sourceDocumentNodes) > 1 && null === $builder->getPrimaryAlias()) {
            throw new \InvalidArgumentException(
                'You must specify a primary alias when selecting from multiple document sources' .
                'e.g. $qb->from(\'a\') ...'
            );
        }

        $this->applySourceConstraints($builder);

        $phpcrQuery = $this->qomf->createQuery(
            $this->from,
            $this->constraint,
            $this->orderings,
            $this->columns
        );

        $query = new Query($phpcrQuery, $this->eventDispatcher, $this->locale, array(), $builder->getPrimaryAlias());

        if ($firstResult = $builder->getFirstResult()) {
            $query->setFirstResult($firstResult);
        }

        if ($maxResults = $builder->getMaxResults()) {
            $query->setMaxResults($maxResults);
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    protected function applySourceConstraints(QueryBuilder $builder)
    {
        foreach ($this->sourceDocumentNodes as $sourceDocumentNode) {
            $metadata = $this->getMetadata($sourceDocumentNode->getDocumentFqn());
            $constraint = $this->strategy->createSourceConstraint(
                $this->qomf, 
                $sourceDocumentNode->getAlias(),
                $metadata->getClass()
            );

            if (null === $constraint) {
                continue;
            }

            if ($this->constraint) {
                $this->constraint = $this->qomf->andConstraint(
                    $this->constraint,
                    $constraint
                );
                continue;
            }

            $this->constraint = $constraint;
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function walkSourceDocument(SourceDocument $node)
    {
        $alias = $node->getAlias();
        $documentFqn = $node->getDocumentFqn();

        $this->sourceDocumentNodes[$alias] = $node;

        $this->documentMetadata[$alias] = $this->getMetadata($documentFqn);

        $alias = $this->qomf->selector(
            $alias,
            $this->strategy->getPrimaryNodeType($documentFqn)
        );

        return $alias;
    }

    /**
     * {@inheritDoc}
     */
    protected function walkOperandDynamicField(OperandDynamicField $node)
    {
        $alias = $node->getAlias();
        $field = $node->getField();

        list($alias, $phpcrProperty) = $this->getPhpcrProperty(
            $alias,
            $field
        );

        $operand = $this->qomf->propertyValue(
            $alias,
            $phpcrProperty
        );

        return $operand;
    }

    /**
     * Return the PHPCR property name and alias for the given document
     * field name and query alias.
     *
     * @param string $alias As specified in the query source.
     * @param string $field Name of the document field
     *
     * @return array {
     *     @var string Element is the real alias to use, second element is
     *     @var string the property name
     * }
     */
    protected function getPhpcrProperty($alias, $field)
    {
        if (!isset($this->documentMetadata[$alias])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown document alias "%s". Known aliases: "%s"',
                $alias,
                implode('", "', array_keys($this->documentMetadata))
            ));
        }

        $metadata = $this->documentMetadata[$alias];
        $fieldMapping = $metadata->getFieldMapping($field);
        $phpcrName = $this->encoder->encode(
            $fieldMapping['encoding'],
            $fieldMapping['property'],
            $this->locale
        );

        return array($alias, $phpcrName);
    }

    /**
     * Return either the metadata for the fqn of the document, or the alias
     *
     * @param string $documentFqn Document FQN or alias
     * @return Metadata
     */
    protected function getMetadata($documentFqn)
    {
        if ($this->metadataFactory->hasAlias($documentFqn)) {
            return $this->metadataFactory->getMetadataForAlias($documentFqn);
        }

        return $this->metadataFactory->getMetadataForClass($documentFqn);
    }

}
