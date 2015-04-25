<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\Mapping;

use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\TimestampSubscriber;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\TimestampBehavior;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\UuidSubscriber;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

class UuidSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->notImplementing = new \stdClass;
        $this->node = $this->prophesize(NodeInterface::class);
        $this->document = new TestUuidDocument();
        $this->accessor = new DocumentAccessor($this->document);

        $this->subscriber = new UuidSubscriber();
    }

    /**
     * It should return early when not implementing
     */
    public function testHydrateNotImplementing()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->handleUuid($this->hydrateEvent->reveal());
    }

    /**
     * It should set the node name on the document
     */
    public function testUuid()
    {
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getDocument()->willReturn($this->document);
        $this->hydrateEvent->getAccessor()->willReturn($this->accessor);
        $this->node->getIdentifier()->willReturn('hello');

        $this->subscriber->handleUuid($this->hydrateEvent->reveal());

        $this->assertEquals('hello', $this->document->getUuid());
    }

}

class TestUuidDocument implements UuidBehavior
{
    private $uuid;

    public function getUuid()
    {
        return $this->uuid;
    }
}