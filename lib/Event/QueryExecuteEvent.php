<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

use Sulu\Component\DocumentManager\Collection\QueryResultCollection;
use Sulu\Component\DocumentManager\DocumentManagerContext;
use Sulu\Component\DocumentManager\Query\Query;

class QueryExecuteEvent extends AbstractDocumentManagerContextEvent
{
    use EventOptionsTrait;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var QueryResultCollection
     */
    private $result;

    /**
     * @param DocumentManagerContext $context
     * @param Query $query
     * @param array $options
     */
    public function __construct(DocumentManagerContext $context, Query $query, array $options = [])
    {
        parent::__construct($context);
        $this->query = $query;
        $this->options = $options;
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param QueryResultCollection $collection
     */
    public function setResult(QueryResultCollection $collection)
    {
        $this->result = $collection;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }
}
