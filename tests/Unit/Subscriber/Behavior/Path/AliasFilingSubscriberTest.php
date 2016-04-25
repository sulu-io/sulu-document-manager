<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\Audit\Path;

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Behavior\Path\AliasFilingBehavior;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Path\AliasFilingSubscriber;

class AliasFilingSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PersistEvent
     */
    private $persistEvent;

    /**
     * @var \stdClass
     */
    private $notImplementing;

    /**
     * @var AliasFilingBehavior
     */
    private $document;

    /**
     * @var \stdClass
     */
    private $parentDocument;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var MetaData
     */
    private $metadata;

    /**
     * @var NodeInterface
     */
    private $parentNode;

    /**
     * @var AliasFilingSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->notImplementing = new \stdClass();
        $this->document = $this->prophesize(AliasFilingBehavior::class);
        $this->parentDocument = new \stdClass();
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->documentManager = $this->prophesize(DocumentManager::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->manager = $this->prophesize(DocumentManagerInterface::class);

        $this->persistEvent->getNodeManager()->willReturn($this->nodeManager->reveal());
        $this->persistEvent->getMetadataFactory()->willReturn($this->metadataFactory->reveal());
        $this->persistEvent->getManager()->willReturn($this->manager->reveal());
        $this->persistEvent->getOptions()->willReturn([]);

        $this->subscriber = new AliasFilingSubscriber();
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
     * It should set the parent document.
     */
    public function testSetParentDocument()
    {
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->getLocale()->willReturn('fr');
        $this->metadataFactory->getMetadataForClass(get_class($this->document->reveal()))->willReturn(
            $this->metadata->reveal()
        );
        $this->metadata->getAlias()->willReturn('test');
        $this->nodeManager->createPath('/tests')->willReturn($this->parentNode->reveal());
        $this->persistEvent->hasParentNode()->shouldBeCalled();
        $this->persistEvent->setParentNode($this->parentNode->reveal())->shouldBeCalled();
        $this->documentManager->find('/tests', 'fr')->willReturn($this->parentDocument);

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should return early if the event already has a parent node.
     */
    public function testReturnEarlyAlreadyHasParent()
    {
        $this->persistEvent->getOptions()->willReturn(['path' => '/path']);
        $this->persistEvent->setParentNode(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }
}
