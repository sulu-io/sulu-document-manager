<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\Mapping;

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\ProxyFactory;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\ParentSubscriber;
use Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\SubscriberTestCase;

class ParentSubscriberTest extends SubscriberTestCase
{
    /**
     * @var HydrateEvent
     */
    private $hydrateEvent;

    /**
     * @var MoveEvent
     */
    private $moveEvent;

    /**
     * @var ParentBehavior
     */
    private $document;

    /**
     * @var \stdClass
     */
    private $notImplementing;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var NodeInterface
     */
    private $parentNode;

    /**
     * @var \stdClass
     */
    private $parentDocument;

    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var ParentSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->moveEvent = $this->prophesize(MoveEvent::class);
        $this->document = $this->prophesize(ParentBehavior::class);
        $this->notImplementing = new \stdClass();
        $this->node = $this->prophesize(NodeInterface::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->parentDocument = new \stdClass();
        $this->proxyFactory = $this->prophesize(ProxyFactory::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->documentManager = $this->prophesize(DocumentManager::class);

        $this->manager = $this->prophesize(DocumentManagerInterface::class);
        $this->manager->getProxyFactory()->willReturn($this->proxyFactory->reveal());
        $this->manager->getInspector()->willReturn($this->inspector->reveal());

        $this->hydrateEvent->getManager()->willReturn($this->manager->reveal());
        $this->moveEvent->getManager()->willReturn($this->manager->reveal());

        $this->subscriber = new ParentSubscriber();

        $this->hydrateEvent->getNode()->willReturn($this->node);
    }

    /**
     * It should return early if the document does not implement the ParentBehavior interface.
     */
    public function testHydrateNotImplementing()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should populate the documents parent property with a proxy.
     */
    public function testHydrateParent()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal());
        $this->hydrateEvent->getOptions()->willReturn(['test' => true]);

        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->node->getDepth()->willReturn(2);

        $this->proxyFactory->createProxyForNode($this->document->reveal(), $this->parentNode->reveal(), ['test' => true])
            ->willReturn($this->parentDocument);
        $this->parentNode->hasProperty('jcr:uuid')->willReturn(true);

        $this->document->setParent($this->parentDocument)->shouldBeCalled();

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should not map the parent if the parent node has no UUID property.
     */
    public function testHydrateParentNoUuid()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal());
        $this->hydrateEvent->getOptions()->willReturn(['test' => true]);

        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->node->getDepth()->willReturn(2);
        $this->parentNode->hasProperty('jcr:uuid')->willReturn(false);

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should throw an exception if the node for the document is a root node.
     *
     * @expectedException \RuntimeException
     */
    public function testThrowExceptionRootNode()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal());

        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->node->getDepth()->willReturn(0);

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    public function testMove()
    {
        $this->moveEvent->getDocument()->willReturn($this->document);
        $this->inspector->getNode($this->document)->willReturn($this->node);

        $this->node->getParent()->willReturn($this->parentNode);
        $this->parentNode->hasProperty('jcr:uuid')->willReturn(true);
        $this->document->setParent(Argument::any())->shouldBeCalled();

        $this->subscriber->handleMove($this->moveEvent->reveal());
    }

    /**
     * It should set the parent node on the documnent.
     */
    public function testSetParentNodeFromDocument()
    {
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->hasParentNode()->willReturn(false);
        $this->persistEvent->getManager()->willReturn($this->manager->reveal());
        $this->persistEvent->setParentNode($this->node->reveal())->shouldBeCalled();
        $this->document->getParent()->willReturn($this->parentDocument);
        $this->inspector->getNode($this->parentDocument)->willReturn($this->node->reveal());

        $this->subscriber->handleSetParentNodeFromDocument($this->persistEvent->reveal());
    }
}
