<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Collection;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Component\DocumentManager\DocumentManagerContext;

/**
 * Lazily load documents referring to the given node.
 */
class ReferrerCollection extends AbstractLazyCollection
{
    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var DocumentManagerContext
     */
    private $context;

    public function __construct(NodeInterface $node, DocumentManagerContext $context, $locale)
    {
        $this->node = $node;
        $this->context = $context;
        $this->locale = $locale;
        $this->documents = new \ArrayIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $this->initialize();
        $referrerNode = $this->documents->current();

        $hydrateEvent = new HydrateEvent($referrerNode, $this->locale);
        $hydrateEvent->attachContext($this->context);
        $this->context->getEventDispatcher()->dispatch(Events::HYDRATE, $hydrateEvent);

        return $hydrateEvent->getDocument();
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        if (true === $this->initialized) {
            return;
        }

        $references = $this->node->getReferences();

        // TODO: Performance: calling getParent adds overhead when the collection is
        //       initialized, but if we don't do this, we won't know how many items are in the
        //       collection, as one node could have many referring properties.
        foreach ($references as $reference) {
            /* @var PropertyInterface $reference */
            $referrerNode = $reference->getParent();
            $this->documents[$referrerNode->getIdentifier()] = $referrerNode;
        }

        $this->initialized = true;
    }
}
