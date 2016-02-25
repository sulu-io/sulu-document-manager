<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Comonent\DocumentManager\Tests\Unit\Subscriber;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\DocumentManagerContext;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\ReorderSubscriber;

class ReorderSubscriberTest extends \PHPUnit_Framework_TestCase
{
    const UUID = 'c6a14fa1-6e8f-4802-8b2c-9b33606cb063';
    const SIBLING_PATH = '/path/to/sibling';
    const PARENT_PATH = '/path/to';
    const NODE_NAME = 'node';

    public function setUp()
    {
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->siblingNode = $this->prophesize(NodeInterface::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->document = new \stdClass();
        $this->registry = $this->prophesize(DocumentRegistry::class);
        $this->event = $this->prophesize(ReorderEvent::class);

        $this->context = $this->prophesize(DocumentManagerContext::class);
        $this->context->getNodeManager()->willReturn($this->nodeManager->reveal());
        $this->context->getRegistry()->willReturn($this->registry->reveal());

        $this->event->getContext()->willReturn($this->context->reveal());

        $this->subscriber = new ReorderSubscriber();

        $this->event->getDocument()->willReturn($this->document);
        $this->registry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
    }

    /**
     * It should reorder with a UUID target.
     */
    public function testReorderUuidTarget()
    {
        $this->event->getDestId()->willReturn(self::UUID);
        $this->nodeManager->find(self::UUID)->willReturn($this->siblingNode->reveal());
        $this->event->getAfter()->willReturn(false);
        $this->siblingNode->getPath()->willReturn(self::SIBLING_PATH);
        $this->parentNode->getPath()->willReturn(self::PARENT_PATH);
        $this->node->getName()->willReturn(self::NODE_NAME);
        $this->node->getParent()->willReturn($this->parentNode->reveal());

        $this->parentNode->orderBefore(self::NODE_NAME, 'sibling')->shouldBeCalled();

        $this->subscriber->handleReorder($this->event->reveal());
    }

    /**
     * It should throw an exception if the target is not a sibling.
     *
     * @expectedException Sulu\Component\DocumentManager\Exception\DocumentManagerException
     */
    public function testExceptionTargetNotSibling()
    {
        $this->event->getDestId()->willReturn(self::UUID);
        $this->nodeManager->find(self::UUID)->willReturn($this->siblingNode->reveal());
        $this->event->getAfter()->willReturn(false);

        $this->siblingNode->getPath()->willReturn('/path/to/foo/bar/boo');
        $this->parentNode->getPath()->willReturn(self::PARENT_PATH);
        $this->node->getName()->willReturn(self::NODE_NAME);
        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->node->getPath()->willReturn('/test');

        $this->subscriber->handleReorder($this->event->reveal());
    }

    /**
     * It should order after a sibling which is not the last node.
     */
    public function testOrderBeforeSiblingNotLast()
    {
        $siblings = [
            'one' => $this->prophesize(NodeInterface::class),
            'two' => $this->prophesize(NodeInterface::class),
        ];

        $this->event->getDestId()->willReturn(self::UUID);
        $this->nodeManager->find(self::UUID)->willReturn($this->siblingNode->reveal());
        $this->event->getAfter()->willReturn(true);
        $this->parentNode->getPath()->willReturn(self::PARENT_PATH);
        $this->node->getName()->willReturn(self::NODE_NAME);
        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->parentNode->getNodes()->willReturn($siblings);

        $this->siblingNode->getPath()->willReturn('/path/to/one');
        $this->parentNode->orderBefore(self::NODE_NAME, 'two')->shouldBeCalled();

        $this->subscriber->handleReorder($this->event->reveal());
    }

    /**
     * It should order after the last node.
     */
    public function testOrderAfterLast()
    {
        $siblings = [
            'one' => $this->prophesize(NodeInterface::class),
            'two' => $this->prophesize(NodeInterface::class),
        ];

        $this->event->getDestId()->willReturn(self::UUID);
        $this->nodeManager->find(self::UUID)->willReturn($this->siblingNode->reveal());
        $this->event->getAfter()->willReturn(true);
        $this->parentNode->getPath()->willReturn(self::PARENT_PATH);
        $this->node->getName()->willReturn(self::NODE_NAME);
        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->parentNode->getNodes()->willReturn($siblings);

        $this->siblingNode->getPath()->willReturn('/path/to/two');
        $this->parentNode->orderBefore(self::NODE_NAME, null)->shouldBeCalled();

        $this->subscriber->handleReorder($this->event->reveal());
    }
}
