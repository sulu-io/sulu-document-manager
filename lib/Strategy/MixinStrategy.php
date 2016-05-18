<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Strategy;

use PHPCR\NodeInterface;
use PHPCR\Util\UUIDHelper;
use Sulu\Component\DocumentManager\DocumentStrategyInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;

/**
 * Manage nodes via. a jcr mixin.
 */
class MixinStrategy implements DocumentStrategyInterface
{
    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @param MetadataFactoryInterface $metadataFactory
     */
    public function __construct(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createNodeForDocument($document, NodeInterface $parentNode, $name)
    {
        $uuid = UUIDHelper::generateUUID();
        $name = $name ?: $uuid;

        $metadata = $this->metadataFactory->getMetadataForClass(get_class($document));

        $node = $parentNode->addNode($name);
        $node->addMixin($metadata->getPhpcrType());
        $node->setProperty('jcr:uuid', $uuid);

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveMetadataForNode(NodeInterface $node)
    {
        if (false === $node->hasProperty('jcr:mixinTypes')) {
            return;
        }

        $mixinTypes = (array) $node->getPropertyValue('jcr:mixinTypes');

        foreach ($mixinTypes as $mixinType) {
            if (true == $this->metadataFactory->hasMetadataForPhpcrType($mixinType)) {
                return $this->metadataFactory->getMetadataForPhpcrType($mixinType);
            }
        }

        return;
    }
}
