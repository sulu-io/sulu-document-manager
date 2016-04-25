<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit;

use Prophecy\Argument;
use Sulu\Component\DocumentManager\Collection\QueryResultCollection;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentManagerContext;
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\CreateEvent;
use Sulu\Component\DocumentManager\Event\FindEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\QueryCreateEvent;
use Sulu\Component\DocumentManager\Event\QueryExecuteEvent;
use Sulu\Component\DocumentManager\Event\RefreshEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\DocumentManager\Query\Query;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class DocumentManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var mixed
     */
    private $dispatcher;

    /**
     * @var DocumentManager
     */
    private $manager;

    /**
     * @var object
     */
    private $document;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var QueryResultCollection
     */
    private $resultCollection;

    public function setUp()
    {
        $this->dispatcher = new EventDispatcher();
        $this->context = $this->prophesize(DocumentManagerContext::class);
        $this->context->getEventDispatcher()->willReturn($this->dispatcher);
        $this->context->attachManager(Argument::type(DocumentManager::class))->shouldBeCalled();

        $this->manager = new DocumentManager(
            $this->context->reveal()
        );

        $this->document = new \stdClass();

        $this->query = $this->prophesize(Query::class);
        $this->resultCollection = $this->prophesize(QueryResultCollection::class);
    }

    /**
     * It should issue a persist event for the passed document.
     */
    public function testPersist()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->persist(new \stdClass(), 'fr');
        $this->assertTrue($subscriber->persist);
    }

    /**
     * It should issue a remove event.
     */
    public function testRemove()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->remove(new \stdClass());
        $this->assertTrue($subscriber->remove);
    }

    /**
     * It issue a reorder event.
     */
    public function testReorder()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->reorder(new \stdClass(), 1234);
        $this->assertTrue($subscriber->reorder);
    }

    /**
     * It should issue a move event.
     */
    public function testMove()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->move(new \stdClass(), '/path/to');
        $this->assertTrue($subscriber->move);
    }

    /**
     * It should issue a copy event.
     */
    public function testCopy()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->copy(new \stdClass(), '/path/to');
        $this->assertTrue($subscriber->copy);
    }

    /**
     * It should issue a create event.
     */
    public function testCreate()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->create('foo');
        $this->assertTrue($subscriber->create);
    }

    /**
     * It should issue a refresh event.
     */
    public function testRefresh()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->refresh($this->document);
        $this->assertTrue($subscriber->refresh);
    }

    /**
     * It should issue a clear event.
     */
    public function testClear()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->clear();
        $this->assertTrue($subscriber->clear);
    }

    /**
     * It should issue a flush event.
     */
    public function testFlush()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->flush();
        $this->assertTrue($subscriber->flush);
    }

    /**
     * It should issue a find event.
     */
    public function testFind()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->find('foo', 'fr');
        $this->assertTrue($subscriber->find);
    }

    /**
     * It should throw an exception with invalid options.
     *
     * @expectedException Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function testFindWithInvalidOptions()
    {
        $this->addSubscriber();
        $this->manager->find('foo', 'bar', ['foo123' => 'bar']);
        $this->assertInstanceOf(UndefinedOptionsException::class, $e->getPrevious());
    }

    /**
     * It should pass options.
     */
    public function testFindWithOptions()
    {
        $this->addSubscriber();
        $this->manager->find('foo', 'bar', ['test.foo' => 'bar']);
    }

    /**
     * It should issue a query create event.
     */
    public function testQueryCreate()
    {
        $subscriber = $this->addSubscriber();
        $query = $this->manager->createQuery('SELECT foo FROM [foo:bar]', 'fr');
        $this->assertTrue($subscriber->queryCreate);
        $this->assertInstanceOf(Query::class, $query);
    }

    /**
     * It should issue a query builder create event.
     *
     * NOT SUPPORTED
     */
    public function testQueryCreateBuilder()
    {
        $this->markTestSkipped('Not supported yet');
    }

    /**
     * It should set the document manager name on instances of DocumentManagerException.
     *
     * @expectedException \Sulu\Component\DocumentManager\Exception\DocumentManagerException
     * @expectedExceptionMessage [default] Hello
     */
    public function testDocumentManagerExceptionName()
    {
        $this->context->getName()->willReturn('default');
        $this->addSubscriber(new DocumentManagerException('Hello'));
        $this->manager->find('foo');
    }

    /**
     * It should not wrap exceptions which do not extend DocumentManagerException.
     */
    public function testNonDocumentManagerException()
    {
        try {
            $exception = $exception = new \Exception('Hello');
            $this->addSubscriber($exception);
            $this->manager->find('foo');
            $this->fail('No exception thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    private function addSubscriber(\Exception $exception = null)
    {
        $subscriber = new TestDocumentManagerSubscriber($this->query->reveal(), $this->resultCollection->reveal(), $exception);
        $this->dispatcher->addSubscriber($subscriber);

        return $subscriber;
    }
}

class TestDocumentManagerSubscriber implements EventSubscriberInterface
{
    public $persist = false;
    public $hydrate = false;
    public $remove = false;
    public $reorder = false;
    public $copy = false;
    public $move = false;
    public $create = false;
    public $clear = false;
    public $flush = false;
    public $find = false;
    public $queryCreate = false;
    public $queryCreateBuilder = false;
    public $queryExecute = false;
    public $refresh = false;

    private $query;
    private $resultCollection;
    private $exception;

    public function __construct(Query $query, QueryResultCollection $resultCollection, \Exception $exception = null)
    {
        $this->query = $query;
        $this->resultCollection = $resultCollection;
        $this->exception = $exception;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => 'handlePersist',
            Events::REMOVE => 'handleRemove',
            Events::MOVE => 'handleMove',
            Events::COPY => 'handleCopy',
            Events::CREATE => 'handleCreate',
            Events::CLEAR => 'handleClear',
            Events::FLUSH => 'handleFlush',
            Events::FIND => 'handleFind',
            Events::QUERY_CREATE => 'handleQueryCreate',
            Events::QUERY_CREATE_BUILDER => 'handleQueryBuilderCreate',
            Events::QUERY_EXECUTE => 'handleQueryExecute',
            Events::REFRESH => 'handleRefresh',
            Events::REORDER => 'handleReorder',
            Events::CONFIGURE_OPTIONS => 'handleConfigureOptions',
        ];
    }

    public function handleConfigureOptions(ConfigureOptionsEvent $event)
    {
        $options = $event->getOptions();
        $options->setDefaults([
            'test.foo' => 'bar',
        ]);
    }

    public function handlePersist(PersistEvent $event)
    {
        $this->persist = true;
    }

    public function handleRemove(RemoveEvent $event)
    {
        $this->remove = true;
    }

    public function handleCopy(CopyEvent $event)
    {
        $this->copy = true;
    }

    public function handleMove(MoveEvent $event)
    {
        $this->move = true;
    }

    public function handleCreate(CreateEvent $event)
    {
        $this->create = true;
        $event->setDocument(new \stdClass());
    }

    public function handleClear(ClearEvent $event)
    {
        $this->clear = true;
    }

    public function handleFlush(FlushEvent $event)
    {
        $this->flush = true;
    }

    public function handleFind(FindEvent $event)
    {
        if ($this->exception) {
            throw $this->exception;
        }
        $this->find = true;
        $event->setDocument(new \stdClass());
    }

    public function handleQueryCreate(QueryCreateEvent $event)
    {
        $this->queryCreate = true;
        $event->setQuery($this->query);
    }

    public function handleQueryExecute(QueryExecuteEvent $event)
    {
        $this->queryExecute = true;
        $event->setResult($this->resultCollection);
    }

    public function handleRefresh(RefreshEvent $event)
    {
        $this->refresh = true;
    }

    public function handleReorder(ReorderEvent $event)
    {
        $this->reorder = true;
    }
}
