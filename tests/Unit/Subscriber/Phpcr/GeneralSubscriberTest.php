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
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\RefreshEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\GeneralSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;

class GeneralSubscriberTest extends \PHPUnit_Framework_TestCase
{
    const SRC_PATH = '/path/to';
    const DST_PATH = '/dest/path';
    const DST_NAME = 'foo';

    public function setUp()
    {
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcher::class);

        $this->moveEvent = $this->prophesize(MoveEvent::class);
        $this->copyEvent = $this->prophesize(CopyEvent::class);
        $this->clearEvent = $this->prophesize(ClearEvent::class);
        $this->flushEvent = $this->prophesize(FlushEvent::class);
        $this->refreshEvent = $this->prophesize(RefreshEvent::class);

        $this->document = new \stdClass();
        $this->node = $this->prophesize(NodeInterface::class);

        $this->manager = $this->prophesize(DocumentManagerInterface::class);

        $this->moveEvent->getManager()->willReturn($this->manager->reveal());
        $this->copyEvent->getManager()->willReturn($this->manager->reveal());
        $this->clearEvent->getManager()->willReturn($this->manager->reveal());
        $this->context = $this->prophesize(DocumentManagerContext::class);
        $this->flushEvent->getManager()->willReturn($this->manager->reveal());
        $this->refreshEvent->getManager()->willReturn($this->manager->reveal());
        $this->refreshEvent->getContext()->willReturn($this->context->reveal());
        $this->refreshEvent->getEventDispatcher()->willReturn($this->eventDispatcher->reveal());

        $this->refreshEvent->getRegistry()->willReturn($this->documentRegistry->reveal());
        $this->moveEvent->getRegistry()->willReturn($this->documentRegistry->reveal());
        $this->moveEvent->getNodeManager()->willReturn($this->nodeManager->reveal());
        $this->copyEvent->getRegistry()->willReturn($this->documentRegistry->reveal());
        $this->copyEvent->getNodeManager()->willReturn($this->nodeManager->reveal());
        $this->clearEvent->getNodeManager()->willReturn($this->nodeManager->reveal());
        $this->flushEvent->getNodeManager()->willReturn($this->nodeManager->reveal());

        $this->subscriber = new GeneralSubscriber();
    }

    /**
     * It should move a document.
     */
    public function testHandleMove()
    {
        $this->moveEvent->getDocument()->willReturn($this->document);
        $this->moveEvent->getDestId()->willReturn(self::DST_PATH);
        $this->moveEvent->getDestName()->willReturn(self::DST_NAME);

        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $this->node->getPath()->willReturn(self::SRC_PATH);

        $this->nodeManager->move(self::SRC_PATH, self::DST_PATH, self::DST_NAME)->shouldBeCalled();

        $this->subscriber->handleMove($this->moveEvent->reveal());
    }

    /**
     * It should copy a document.
     */
    public function testHandleCopy()
    {
        $this->copyEvent->getDocument()->willReturn($this->document);
        $this->copyEvent->getDestId()->willReturn(self::DST_PATH);
        $this->copyEvent->getDestName()->willReturn(self::DST_NAME);
        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $this->node->getPath()->willReturn(self::SRC_PATH);
        $this->nodeManager->copy(self::SRC_PATH, self::DST_PATH, self::DST_NAME)->willReturn('foobar');
        $this->copyEvent->setCopiedPath('foobar')->shouldBeCalled();

        $this->subscriber->handleCopy($this->copyEvent->reveal());
    }

    /**
     * It should clear/reset the PHPCR session.
     */
    public function testHandleClear()
    {
        $this->nodeManager->clear()->shouldBeCalled();
        $this->subscriber->handleClear($this->clearEvent->reveal());
    }

    /**
     * It should save the PHPCR session.
     */
    public function testHandleFlush()
    {
        $this->nodeManager->save()->shouldBeCalled();
        $this->subscriber->handleFlush($this->flushEvent->reveal());
    }

    /**
     * It should refresh a document.
     */
    public function testHandleRefresh()
    {
        $this->refreshEvent->getDocument()->willReturn($this->document);
        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $this->node->revert()->shouldBeCalled();
        $this->documentRegistry->getLocaleForDocument($this->document)->willReturn('fr');

        $event = new HydrateEvent($this->context->reveal(), $this->node->reveal(), 'fr');
        $this->eventDispatcher->dispatch(Events::REFRESH, $event);

        $this->subscriber->handleRefresh($this->refreshEvent->reveal());
    }
}
