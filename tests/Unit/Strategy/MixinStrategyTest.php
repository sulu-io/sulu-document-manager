<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Comonent\DocumentManager\tests\Unit\Strategy;

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\Strategy\MixinStrategy;
use Sulu\Component\DocumentManager\Strategy\Strategy;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

class MixinStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MetadataFactoryInterface
     */
    private $factory;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var MixinStrategy
     */
    private $strategy;

    /**
     * @var object
     */
    private $document;

    /**
     * @var UuidBehavior
     */
    private $uuidDocument;

    /**
     * @var NodeInterface
     */
    private $parentNode;

    /**
     * @var NodeInterface
     */
    private $node;

    public function setUp()
    {
        $this->factory = $this->prophesize(MetadataFactoryInterface::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->strategy = new MixinStrategy($this->factory->reveal());

        $this->document = new \stdClass();
        $this->uuidDocument = $this->prophesize(UuidBehavior::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
    }

    /**
     * It should create a PHPCR node for the given document and add the
     * PHPCR type as a mixin and set the UUID.
     */
    public function testCreateNode()
    {
        $name = 'hello';

        $this->factory->getMetadataForClass('stdClass')->willReturn($this->metadata->reveal());
        $this->metadata->getPhpcrType()->willReturn('someType');
        $this->parentNode->addNode($name)->willReturn($this->node->reveal());
        $this->node->addMixin('someType')->shouldBeCalled();
        $this->node->setProperty('jcr:uuid', Argument::type('string'))->shouldBeCalled();

        $this->strategy->createNodeForDocument($this->document, $this->parentNode->reveal(), $name);
    }

    /**
     * It should not set the UUID if the document already has one.
     */
    public function testCreateNodeKeepExistingUuid()
    {
        $name = 'hello';
        $uuid = '1234';

        $this->uuidDocument->getUuid()->willReturn($uuid);
        $this->factory->getMetadataForClass(get_class($this->uuidDocument->reveal()))->willReturn($this->metadata->reveal());
        $this->metadata->getPhpcrType()->willReturn('someType');
        $this->parentNode->addNode($name)->willReturn($this->node->reveal());
        $this->node->addMixin('someType')->shouldBeCalled();
        $this->node->setProperty('jcr:uuid', $uuid)->shouldBeCalled();

        $this->strategy->createNodeForDocument($this->uuidDocument->reveal(), $this->parentNode->reveal(), $name);
    }

    /**
     * It should resolve metadata for the given PHPCR node.
     */
    public function testResolveMetadata()
    {
        $mixinTypes = ['foobar'];
        $this->node->hasProperty('jcr:mixinTypes')->willReturn(true);
        $this->node->getPropertyValue('jcr:mixinTypes')->willReturn($mixinTypes);

        $this->factory->hasMetadataForPhpcrType('foobar')->willReturn(true);
        $this->factory->getMetadataForPhpcrType('foobar')->willReturn($this->metadata->reveal());

        $result = $this->strategy->resolveMetadataForNode($this->node->reveal());
        $this->assertSame($this->metadata->reveal(), $result);
    }

    /**
     * It should return NULL if the document is not managed.
     */
    public function testResolveMetadataNotManaged()
    {
        $mixinTypes = ['foobar'];
        $this->node->hasProperty('jcr:mixinTypes')->willReturn(false);
        $result = $this->strategy->resolveMetadataForNode($this->node->reveal());

        $this->assertNull($result);
    }
}
