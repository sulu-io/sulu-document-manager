<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Comonent\DocumentManager\Tests\Unit\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\RemoveSubscriber;

class RemoveSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @var RemoveEvent
     */
    private $removeEvent;

    /**
     * @var \stdClass
     */
    private $document;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var NodeInterface
     */
    private $node1;

    /**
     * @var NodeInterface
     */
    private $node2;

    /**
     * @var PropertyInterface
     */
    private $property1;

    /**
     * @var PropertyInterface
     */
    private $property2;

    /**
     * @var RemoveSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->removeEvent = $this->prophesize(RemoveEvent::class);
        $this->document = new \stdClass();
        $this->node = $this->prophesize(NodeInterface::class);
        $this->node1 = $this->prophesize(NodeInterface::class);
        $this->node2 = $this->prophesize(NodeInterface::class);
        $this->property1 = $this->prophesize(PropertyInterface::class);
        $this->property2 = $this->prophesize(PropertyInterface::class);

        $this->subscriber = new RemoveSubscriber(
            $this->documentRegistry->reveal(),
            $this->nodeManager->reveal()
        );

        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
    }

    /**
     * It should remove nodes from the PHPCR session.
     */
    public function testHandleRemove()
    {
        $this->removeEvent->getDocument()->willReturn($this->document);
        $this->node->remove()->shouldBeCalled();
        $this->node->getReferences()->willReturn(new \ArrayIterator([]));

        $this->subscriber->handleRemove($this->removeEvent->reveal());
    }

    /**
     * It should not remove nodes if the node is still referenced.
     *
     * @expectedException Sulu\Component\DocumentManager\Exception\DocumentReferencedException
     */
    public function testHandleReferencedRemove()
    {
        $this->removeEvent->getDocument()->willReturn($this->document);
        $this->node->getReferences()->willReturn(new \ArrayIterator([new \stdClass()]));

        $this->subscriber->handleRemove($this->removeEvent->reveal());
    }
}
