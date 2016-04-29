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

use PHPCR\Query\QueryResultInterface;
use Sulu\Component\DocumentManager\DocumentManagerContext;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;

/**
 * Lazily hydrate query results.
 */
class QueryResultCollection extends AbstractLazyCollection
{
    /**
     * @var QueryResultInterface
     */
    private $result;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var array
     */
    private $options;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var null|string
     */
    private $primarySelector = null;

    /**
     * @var DocumentManagerInterface
     */
    private $context;

    public function __construct(
        QueryResultInterface $result,
        DocumentManagerContext $context,
        $locale,
        $options = [],
        $primarySelector = null
    ) {
        $this->result = $result;
        $this->context = $context;
        $this->locale = $locale;
        $this->options = $options;
        $this->primarySelector = $primarySelector;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $this->initialize();
        $row = $this->documents->current();
        $node = $row->getNode($this->primarySelector);

        $hydrateEvent = new HydrateEvent($this->context, $node, $this->locale, $this->options);
        $hydrateEvent->getEventDispatcher()->dispatch(Events::HYDRATE, $hydrateEvent);

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

        $this->documents = $this->result->getRows();
        $this->initialized = true;
    }
}
