<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\Mapping\Mapping;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\NodeNameSubscriber;
use Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\SubscriberTestCase;

class NodeNameSubscriberTest extends SubscriberTestCase
{
    public function setUp()
    {
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->notImplementing = new \stdClass();
        $this->node = $this->prophesize(NodeInterface::class);
        $this->document = new TestNodeNameDocument();
        $this->accessor = new DocumentAccessor($this->document);

        $this->subscriber = new NodeNameSubscriber();
    }

    /**
     * It should return early when not implementing.
     */
    public function testHydrateNotImplementing()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->handleNodeName($this->hydrateEvent->reveal());
    }

    /**
     * It should set the node name on the document.
     */
    public function testHydrate()
    {
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getDocument()->willReturn($this->document);
        $this->hydrateEvent->getAccessor()->willReturn($this->accessor);
        $this->node->getName()->willReturn('hello');

        $this->subscriber->handleNodeName($this->hydrateEvent->reveal());

        $this->assertEquals('hello', $this->document->getNodeName());
    }
}

class TestNodeNameDocument implements NodeNameBehavior
{
    private $nodeName;

    public function getNodeName()
    {
        return $this->nodeName;
    }
}
