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
use PHPCR\NodeType\NodeTypeInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\MixinSubscriber;

class MixinSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var MixinSubscriber
     */
    private $mixinSubscriber;

    public function setUp()
    {
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->mixinSubscriber = new MixinSubscriber($this->metadataFactory->reveal());
    }

    public function testSetDocumentMixinsOnNode()
    {
        $event = $this->prophesize(AbstractMappingEvent::class);
        $mixinNode1 = $this->prophesize(NodeTypeInterface::class);
        $mixinNode1->getName()->willReturn('phpcr:type-old-1');
        $mixinNode2 = $this->prophesize(NodeTypeInterface::class);
        $mixinNode2->getName()->willReturn('phpcr:type-old-2');
        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperty('jcr:uuid')->willReturn(false);
        $node->getMixinNodeTypes()->willReturn([$mixinNode1->reveal(), $mixinNode2->reveal()]);
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getPhpcrType()->willReturn('phpcr:type');
        $document = new \stdClass();

        $this->metadataFactory->getMetadataForClass(get_class($document))->willReturn($metadata->reveal());

        $event->getNode()->willReturn($node->reveal());
        $event->getDocument()->willReturn($document);

        $node->removeMixin('phpcr:type-old-1')->shouldBeCalled();
        $node->removeMixin('phpcr:type-old-2')->shouldBeCalled();
        $node->addMixin('phpcr:type')->shouldBeCalled();
        $node->setProperty('jcr:uuid', Argument::type('string'))->shouldBeCalled();

        $this->mixinSubscriber->setDocumentMixinsOnNode($event->reveal());
    }

    public function testSetDocumentMixinsOnNodeWithUuid()
    {
        $event = $this->prophesize(AbstractMappingEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperty('jcr:uuid')->willReturn(true);
        $node->getMixinNodeTypes()->willReturn([]);
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getPhpcrType()->willReturn('phpcr:type');
        $document = new \stdClass();

        $this->metadataFactory->getMetadataForClass(get_class($document))->willReturn($metadata->reveal());

        $event->getNode()->willReturn($node->reveal());
        $event->getDocument()->willReturn($document);

        $node->removeMixin(Argument::cetera())->shouldNotBeCalled();
        $node->addMixin('phpcr:type')->shouldBeCalled();
        $node->setProperty('jcr:uuid', Argument::type('string'))->shouldNotBeCalled();

        $this->mixinSubscriber->setDocumentMixinsOnNode($event->reveal());
    }
}
