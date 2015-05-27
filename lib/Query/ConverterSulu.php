<?php

namespace Sulu\Component\DocumentManager\Query;

use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode as QBConstants;
use Doctrine\ODM\PHPCR\Query\Builder\ConverterPhpcr;
use Doctrine\ODM\PHPCR\Query\Builder\OperandDynamicField;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Builder\SourceDocument;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use PHPCR\SessionInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class which converts a Builder tree to a PHPCR Query.
 */
class ConverterSulu extends ConverterPhpcr
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var Metadata[]
     */
    protected $documentMetadata = array();

    public function __construct(
        SessionInterface $session,
        EventDispatcherInterface $eventDispatcher,
        MetadataFactoryInterface $metadataFactory
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->qomf = $session->getWorkspace()->getQueryManager()->getQOMFactory();
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Returns an ODM Query object from the given ODM (query) Builder.
     *
     * Dispatches the From, Select, Where and OrderBy nodes. Each of these
     * "root" nodes append or set PHPCR QOM objects to corresponding properties
     * in this class, which are subsequently used to create a PHPCR QOM object which
     * is embedded in an ODM Query object.
     *
     * @param QueryBuilder $builder
     *
     * @return Query
     */
    public function getQuery(QueryBuilder $builder)
    {
        $this->documentMetadata = array();
        $this->sourceDocumentNodes = array();
        $this->constraint = null;

        $this->locale = $builder->getLocale();

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
            throw new InvalidArgumentException(
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

        $query = new Query($phpcrQuery, $this->eventDispatcher, $builder->getPrimaryAlias());

        if ($firstResult = $builder->getFirstResult()) {
            $query->setFirstResult($firstResult);
        }

        if ($maxResults = $builder->getMaxResults()) {
            $query->setMaxResults($maxResults);
        }

        return $query;
    }

    protected function applySourceConstraints(QueryBuilder $builder)
    {
        // for each document source add phpcr:{class,classparents} restrictions
        foreach ($this->sourceDocumentNodes as $sourceNode) {
            $phpcrType = $this->documentMetadata[$sourceNode->getAlias()]->getPhpcrType();

            // Jackalope-doctrine-dbal does not support selecting from mixins, and Sulu node types
            // are currently mixins, so we need to explicitly add the mixin criteria.
            $odmClassConstraints = $this->qomf->comparison(
                $this->qomf->propertyValue(
                    $sourceNode->getAlias(),
                    'jcr:mixinTypes'
                ),
                QOMConstants::JCR_OPERATOR_EQUAL_TO,
                $this->qomf->literal($phpcrType)
            );

            if ($this->constraint) {
                $this->constraint = $this->qomf->andConstraint(
                    $this->constraint,
                    $odmClassConstraints
                );
            } else {
                $this->constraint = $odmClassConstraints;
            }
        }
    }

    public function walkSelect(AbstractNode $node)
    {
        $columns = array();

        /** @var $property Field */
        foreach ($node->getChildren() as $property) {
            list($alias, $phpcrName) = $this->getPhpcrProperty(
                $property->getAlias(),
                $property->getField()
            );

            $column = $this->qomf->column(
                $alias,
                $phpcrName,
                // do we want to support custom column names in ODM?
                $phpcrName
            );

            $columns[] = $column;
        }

        $this->columns = $columns;

        return $this->columns;
    }

    protected function walkSourceDocument(SourceDocument $node)
    {
        $alias = $node->getAlias();
        $documentFqn = $node->getDocumentFqn();

        $this->sourceDocumentNodes[$alias] = $node;

        if ($this->metadataFactory->hasAlias($documentFqn)) {
            $meta = $this->metadataFactory->getMetadataForAlias($documentFqn);
        } else {
            $meta = $this->metadataFactory->getMetadataForClass($documentFqn);
        }

        $this->documentMetadata[$alias] = $meta;

        // NOTE: Currently we always select from [nt:unstructured] as Sulu node types
        //       are mixins and jackalope-doctrine-dbal does not support selecting from mixins.
        //
        //       See note in getQuery
        $alias = $this->qomf->selector(
            $alias,
            'nt:unstructured'
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

        $classMeta = $this->documentMetadata[$alias];

        list($alias, $phpcrProperty) = $this->getPhpcrProperty(
            $alias,
            $field
        );

        $op = $this->qomf->propertyValue(
            $alias,
            $phpcrProperty
        );

        return $op;
    }

    /**
     * Return the PHPCR property name and alias for the given ODM document
     * property name and query alias.
     *
     * The alias might change if this is a translated field and the strategy
     * needs to do a join to get in the translation.
     *
     * @param string $originalAlias As specified in the query source.
     * @param string $odmField      Name of ODM document property.
     *
     * @return array first element is the real alias to use, second element is
     *      the property name
     */
    protected function getPhpcrProperty($originalAlias, $odmField)
    {
        if (!isset($this->documentMetadata[$originalAlias])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown document alias "%s". Known aliases: "%s"',
                $originalAlias,
                implode('", "', array_keys($this->documentMetadata))
            ));
        }

        return $originalAlias . '.' . $odmField;
    }
}
