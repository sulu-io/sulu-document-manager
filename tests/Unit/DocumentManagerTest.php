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

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Collection\QueryResultCollection;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\CreateEvent;
use Sulu\Component\DocumentManager\Event\FindEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\QueryCreateEvent;
use Sulu\Component\DocumentManager\Event\RefreshEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\Query\Query;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DocumentManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var DocumentManager
     */
    private $manager;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var \stdClass
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
        $this->dispatcher = $this->prophesize(EventDispatcher::class);
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->manager = new DocumentManager(
            $this->dispatcher->reveal(),
            $this->nodeManager->reveal()
        );

        $this->node = $this->prophesize(NodeInterface::class);
        $this->document = new \stdClass();

        $this->query = $this->prophesize(Query::class);
        $this->resultCollection = $this->prophesize(QueryResultCollection::class);
    }

    /**
     * It should issue a persist event for the passed document.
     */
    public function testPersist()
    {
        $this->dispatcher->dispatch(
            Events::CONFIGURE_OPTIONS,
            Argument::type(ConfigureOptionsEvent::class)
        )->shouldBeCalled();

        $this->dispatcher->dispatch(Events::PERSIST, Argument::type(PersistEvent::class))->shouldBeCalled();
        $this->manager->persist(new \stdClass(), 'fr');
    }

    /**
     * It should issue a remove event.
     */
    public function testRemove()
    {
        $this->dispatcher->dispatch(
            Events::CONFIGURE_OPTIONS,
            Argument::type(ConfigureOptionsEvent::class)
        )->shouldBeCalled();

        $this->dispatcher->dispatch(Events::REMOVE, Argument::type(RemoveEvent::class))->shouldBeCalled();
        $this->manager->remove(new \stdClass());
    }

    /**
     * It should issue a move event.
     */
    public function testMove()
    {
        $this->dispatcher->dispatch(
            Events::CONFIGURE_OPTIONS,
            Argument::type(ConfigureOptionsEvent::class)
        )->shouldNotBeCalled();

        $this->dispatcher->dispatch(Events::MOVE, Argument::type(MoveEvent::class))->shouldBeCalled();
        $this->manager->move(new \stdClass(), '/path/to');
    }

    /**
     * It should issue a copy event.
     */
    public function testCopy()
    {
        $this->dispatcher->dispatch(
            Events::CONFIGURE_OPTIONS,
            Argument::type(ConfigureOptionsEvent::class)
        )->shouldNotBeCalled();

        $this->dispatcher->dispatch(Events::COPY, Argument::type(CopyEvent::class))->shouldBeCalled();
        $this->manager->copy(new \stdClass(), '/path/to');
    }

    /**
     * It should issue a create event.
     */
    public function testCreate()
    {
        $this->dispatcher->dispatch(
            Events::CONFIGURE_OPTIONS,
            Argument::type(ConfigureOptionsEvent::class)
        )->shouldNotBeCalled();

        $document = $this->document;
        $this->dispatcher->dispatch(Events::CREATE, Argument::type(CreateEvent::class))->will(
            function ($arguments) use ($document) {
                $arguments[1]->setDocument($document);
            }
        )->shouldBeCalled();
        $this->manager->create('foo');
    }

    /**
     * It should issue a refresh event.
     */
    public function testRefresh()
    {
        $this->dispatcher->dispatch(
            Events::CONFIGURE_OPTIONS,
            Argument::type(ConfigureOptionsEvent::class)
        )->shouldNotBeCalled();

        $this->dispatcher->dispatch(Events::REFRESH, Argument::type(RefreshEvent::class))->shouldBeCalled();
        $this->manager->refresh($this->document);
    }

    /**
     * It should issue a clear event.
     */
    public function testClear()
    {
        $this->dispatcher->dispatch(
            Events::CONFIGURE_OPTIONS,
            Argument::type(ConfigureOptionsEvent::class)
        )->shouldNotBeCalled();

        $this->dispatcher->dispatch(Events::CLEAR, Argument::type(ClearEvent::class))->shouldBeCalled();
        $this->manager->clear();
    }

    /**
     * It should issue a flush event.
     */
    public function testFlush()
    {
        $this->dispatcher->dispatch(
            Events::CONFIGURE_OPTIONS,
            Argument::type(ConfigureOptionsEvent::class)
        )->shouldNotBeCalled();

        $this->dispatcher->dispatch(Events::FLUSH, Argument::type(FlushEvent::class))->shouldBeCalled();
        $this->manager->flush();
    }

    /**
     * It should issue a find event.
     */
    public function testFind()
    {
        $this->dispatcher->dispatch(
            Events::CONFIGURE_OPTIONS,
            Argument::type(ConfigureOptionsEvent::class)
        )->shouldBeCalled();

        $document = $this->document;
        $this->dispatcher->dispatch(Events::FIND, Argument::type(FindEvent::class))->will(
            function ($arguments) use ($document) {
                $arguments[1]->setDocument($document);
            }
        )->shouldBeCalled();
        $this->manager->find('foo', 'fr');
    }

    /**
     * It should throw an exception with invalid options.
     *
     * @expectedException Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function testFindWithInvalidOptions()
    {
        $this->dispatcher->dispatch(
            Events::CONFIGURE_OPTIONS,
            Argument::type(ConfigureOptionsEvent::class)
        )->shouldBeCalled();

        $this->dispatcher->dispatch(Events::FIND, Argument::type(FindEvent::class))->shouldNotBeCalled();
        $this->manager->find('foo', 'bar', ['foo123' => 'bar']);
    }

    /**
     * It should pass options.
     */
    public function testFindWithOptions()
    {
        $this->dispatcher->dispatch(
            Events::CONFIGURE_OPTIONS,
            Argument::type(ConfigureOptionsEvent::class)
        )->shouldBeCalled();

        $document = $this->document;
        $this->dispatcher->dispatch(Events::FIND, Argument::type(FindEvent::class))->will(
            function ($arguments) use ($document) {
                $arguments[1]->setDocument($document);
            }
        )->shouldBeCalled();
        $this->manager->find('foo', 'bar', ['locale' => 'bar']);
    }

    /**
     * It should issue a query create event.
     */
    public function testQueryCreate()
    {
        $sql2 = 'SELECT foo FROM [foo:bar]';
        $query = $this->prophesize(Query::class);

        $this->dispatcher->dispatch(
            Events::CONFIGURE_OPTIONS,
            Argument::type(ConfigureOptionsEvent::class)
        )->shouldNotBeCalled();

        $this->dispatcher->dispatch(Events::QUERY_CREATE, Argument::type(QueryCreateEvent::class))->will(
            function ($arguments) use ($query) {
                $arguments[1]->setQuery($query->reveal());
            }
        )->shouldBeCalled();

        $query = $this->manager->createQuery($sql2, 'fr');
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
}
