<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\Audit;

use Prophecy\Argument;
use Sulu\Component\DocumentManager\Behavior\Audit\LocalizedTimestampBehavior;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Audit\TimestampSubscriber;

class TimestampSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PersistEvent
     */
    private $persistEvent;

    /**
     * @var HydrateEvent
     */
    private $hydrateEvent;

    /**
     * @var \stdClass
     */
    private $notImplementing;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var DocumentAccessor
     */
    private $accessor;

    /**
     * @var TimestampSubscriber
     */
    private $subscriber;

    /**
     * @var \DateTime
     */
    private $createdDate;

    /**
     * @var \DateTime
     */
    private $changedDate;

    public function setUp()
    {
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->notImplementing = new \stdClass();
        $this->accessor = $this->prophesize(DocumentAccessor::class);

        $this->subscriber = new TimestampSubscriber();

        $this->createdDate = new \DateTime('2015-07-01');
        $this->changedDate = new \DateTime('2015-05-01');
    }

    /**
     * It should return early if the document is not implementing the behavior.
     */
    public function testPersistNotImplementing()
    {
        $this->persistEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should return early if the locale is null.
     */
    public function testPersistLocaleIsNull()
    {
        $document = new TestDocument();
        $this->persistEvent->getDocument()->willReturn($document);
        $this->persistEvent->getLocale()->willReturn(null);
        $this->persistEvent->getAccessor()->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should assign "created" if there is created is actually null.
     */
    public function testPersistCreatedWhenNull()
    {
        $document = new TestDocument();

        $this->persistEvent->getDocument()->willReturn($document);
        $this->persistEvent->getAccessor()->willReturn($this->accessor->reveal());
        $this->persistEvent->getLocale()->willReturn('fr');
        $this->accessor->set('created', Argument::type('DateTime'))->shouldBeCalled();
        $this->accessor->set('changed', Argument::type('DateTime'))->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should always assign "changed".
     */
    public function testPersistChanged()
    {
        $document = new TestDocument($this->createdDate);

        $this->persistEvent->getDocument()->willReturn($document);
        $this->persistEvent->getAccessor()->willReturn($this->accessor->reveal());
        $this->persistEvent->getLocale()->willReturn('fr');
        $this->accessor->set('created', Argument::type('DateTime'))->shouldNotBeCalled();
        $this->accessor->set('changed', Argument::type('DateTime'))->shouldBeCalled();
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }
}

class TestDocument implements LocalizedTimestampBehavior
{
    private $created;
    private $changed;

    public function __construct(\DateTime $created = null, \DateTime $changed = null)
    {
        $this->created = $created;
        $this->changed = $changed;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function getChanged()
    {
        return $this->changed;
    }
}
