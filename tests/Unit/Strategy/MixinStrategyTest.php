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
use PHPCR\Query\QOM\ComparisonInterface;
use PHPCR\Query\QOM\LiteralInterface;
use PHPCR\Query\QOM\PropertyValueInterface;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\Strategy\MixinStrategy;
use Sulu\Component\DocumentManager\Strategy\Strategy;

class MixinStrategyTest extends \PHPUnit_Framework_TestCase
{
    private $factory;
    private $metadata;
    private $strategy;
    private $document;
    private $parentNode;
    private $node;

    public function setUp()
    {
        $this->factory = $this->prophesize(MetadataFactoryInterface::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->strategy = new MixinStrategy($this->factory->reveal());

        $this->document = new \stdClass();
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
    }

    /**
     * It should create a PHPCR node for the given document and add the
     * PHPCR type as a mixin and set the UUID.
     */
    public function testExecutePhpcr()
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

    /**
     * It should return the primary node type for a class fqn
     * It should always return [nt:unstructured].
     */
    public function testGetPrimaryNodeType()
    {
        $this->assertEquals('nt:unstructured', $this->strategy->getPrimaryNodeType('foo'));
        $this->assertEquals('nt:unstructured', $this->strategy->getPrimaryNodeType('bar'));
    }

    /**
     * It should create a source constraint for a given document fqn.
     */
    public function testCreateSourceConstraint()
    {
        $qomf = $this->prophesize(QueryObjectModelFactoryInterface::class);
        $comparison = $this->prophesize(ComparisonInterface::class);
        $propertyValue = $this->prophesize(PropertyValueInterface::class);
        $literal = $this->prophesize(LiteralInterface::class);
        $this->factory->getMetadataForClass('stdClass')->willReturn($this->metadata->reveal());
        $this->metadata->getPhpcrType()->willReturn('foo:bar');

        $qomf->propertyValue('a', 'jcr:mixinTypes')->willReturn($propertyValue->reveal());
        $qomf->literal('foo:bar')->willReturn($literal->reveal());
        $qomf->comparison(
            $propertyValue->reveal(),
            QOMConstants::JCR_OPERATOR_EQUAL_TO,
            $literal->reveal()
        )->willReturn($comparison->reveal());

        $constraint = $this->strategy->createSourceConstraint($qomf->reveal(), 'a', 'stdClass');
        $this->assertSame($comparison->reveal(), $constraint);
    }
}
