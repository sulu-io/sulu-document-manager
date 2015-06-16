<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Metadata;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Document\UnknownDocument;
use Sulu\Component\DocumentManager\DocumentStrategyInterface;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;

/**
 * This class fully implements the MetadataFactoryInterface by composing
 * the "base" metadata factory and the document strategy, which depends also
 * upon the base metadata factory.
 */
class MetadataFactory implements MetadataFactoryInterface
{
    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var DocumentStrategyInterface
     */
    private $strategy;

    /**
     * @param MetadataFactoryInterface $metadataFactory
     * @param DocumentStrategyInterface $strategy
     */
    public function __construct(
        MetadataFactoryInterface $metadataFactory,
        DocumentStrategyInterface $strategy
    ) {
        $this->metadataFactory = $metadataFactory;
        $this->strategy = $strategy;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadataForAlias($alias)
    {
        return $this->metadataFactory->getMetadataForAlias($alias);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadataForPhpcrType($phpcrType)
    {
        return $this->metadataFactory->getMetadataForPhpcrType($phpcrType);
    }

    /**
     * {@inheritDoc}
     */
    public function hasMetadataForPhpcrType($phpcrType)
    {
        return $this->metadataFactory->hasMetadataForPhpcrType($phpcrType);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadataForClass($class)
    {
        return $this->metadataFactory->getMetadataForClass($class);
    }

    /**
     * {@inheritDoc}
     */
    public function hasMetadataForClass($class)
    {
        return $this->metadataFactory->hasMetadataForClass($class);
    }

    /**
     * {@inheritDoc}
     */
    public function hasAlias($alias)
    {
        return $this->metadataFactory->hasAlias($alias);
    }

    /**
     * {@inheritDoc}
     */
    public function getAliases()
    {
        return $this->metadataFactory->getAliases();
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadataForPhpcrNode(NodeInterface $node)
    {
        $metadata = $this->strategy->resolveMetadataForNode($node);

        if (null !== $metadata) {
            return $metadata;
        }

        return $this->getUnknownMetadata();
    }

    /**
     * @return Metadata
     */
    private function getUnknownMetadata()
    {
        $metadata = new Metadata();
        $metadata->setAlias(null);
        $metadata->setPhpcrType(null);
        $metadata->setClass(UnknownDocument::class);

        return $metadata;
    }
}
