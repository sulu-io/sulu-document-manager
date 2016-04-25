<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit\Subscriber\Behavior\Path;

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Behavior\Path\AutoNameBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\DocumentStrategyInterface;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\NameResolver;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Path\AutoNameSubscriber;
use Symfony\Cmf\Bundle\CoreBundle\Slugifier\SlugifierInterface;

class AutoNameSubscriberTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_LOCALE = 'en';

    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @var SlugifierInterface
     */
    private $slugifier;

    /**
     * @var PersistEvent
     */
    private $persistEvent;

    /**
     * @var MoveEvent
     */
    private $moveEvent;

    /**
     * @var AutoNameBehavior
     */
    private $document;

    /**
     * @var \stdClass
     */
    private $parentDocument;

    /**
     * @var NodeInterface
     */
    private $newNode;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var NodeInterface
     */
    private $parentNode;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var \stdClass
     */
    private $parent;

    /**
     * @var NameResolver
     */
    private $resolver;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var DocumentStrategyInterface
     */
    private $strategy;

    /**
     * @var AutoNameSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->slugifier = $this->prophesize(SlugifierInterface::class);
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->moveEvent = $this->prophesize(MoveEvent::class);
        $this->document = $this->prophesize(AutoNameBehavior::class);
        $this->parentDocument = new \stdClass();
        $this->newNode = $this->prophesize(NodeInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->parent = new \stdClass();
        $this->documentRegistry->getDefaultLocale()->willReturn(self::DEFAULT_LOCALE);
        $this->resolver = $this->prophesize(NameResolver::class);
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->strategy = $this->prophesize(DocumentStrategyInterface::class);

        $this->manager = $this->prophesize(DocumentManagerInterface::class);

        $this->moveEvent->getManager()->willReturn($this->manager->reveal());
        $this->moveEvent->getNodeManager()->willReturn($this->nodeManager->reveal());
        $this->moveEvent->getRegistry()->willReturn($this->documentRegistry->reveal());
        $this->persistEvent->getManager()->willReturn($this->manager->reveal());
        $this->persistEvent->getRegistry()->willReturn($this->documentRegistry->reveal());

        $this->subscriber = new AutoNameSubscriber(
            $this->slugifier->reveal(),
            $this->resolver->reveal(),
            $this->strategy->reveal()
        );
    }

    /**
     * It should return early if the document is not an instance of AutoName behavior.
     */
    public function testNotInstanceOfAutoName()
    {
        $document = new \stdClass();
        $this->persistEvent->getOption('auto_name')->willReturn(true);
        $this->persistEvent->hasNode()->willReturn(false);
        $this->persistEvent->getDocument()->willReturn($document);
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should throw an exception if the document has no title.
     *
     * @expectedException Sulu\Component\DocumentManager\Exception\DocumentManagerException
     */
    public function testNoTitle()
    {
        $this->persistEvent->hasNode()->willReturn(false);
        $this->document->getTitle()->willReturn(null);
        $this->persistEvent->getOption('auto_name')->willReturn(true);
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should assign a name based on the documents title.
     */
    public function testAutoName()
    {
        $this->doTestAutoName('hai', 'hai', true, false);
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should not assign a new name, if the option says it is disabled.
     */
    public function testAutoNameWithDisabledOption()
    {
        $this->persistEvent->getOption('auto_name')->willReturn(false);
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->node->rename(Argument::any())->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should rename the node if the document is being saved in the default locale.
     */
    public function testAlreadyHasNode()
    {
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->persistEvent->getLocale()->willReturn(self::DEFAULT_LOCALE);
        $this->doTestAutoName('hai-bye', 'hai-2', false, true);
        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->parentNode->getNodeNames()->willReturn(['hai-bye']);
        $this->node->rename('hai-bye')->shouldBeCalled();
        $this->node->hasNode()->willReturn(true);
        $this->node->getName()->willReturn('foo');

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should not rename the node if the document is being saved a non-default locale.
     */
    public function testAlreadyHasNodeNonDefaultLocale()
    {
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->persistEvent->getLocale()->willReturn('ay');
        $this->doTestAutoName('hai-bye', 'hai-2', false, true);
        $this->node->rename('hai-bye')->shouldNotBeCalled();
        $this->node->hasNode()->willReturn(true);
        $this->node->getName()->willReturn('foo');

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should ensure there is no confict when moving a node.
     */
    public function testMoveConflict()
    {
        $this->moveEvent->getDocument()->willReturn($this->document);
        $this->moveEvent->getDestId()->willReturn(1234);
        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $this->nodeManager->find(1234)->willReturn($this->node->reveal());
        $this->node->getName()->willReturn('foo');
        $this->resolver->resolveName($this->node->reveal(), 'foo')->willReturn('foobar');
        $this->moveEvent->setDestName('foobar')->shouldBeCalled();

        $this->subscriber->handleMove($this->moveEvent->reveal());
    }

    private function doTestAutoName($title, $expectedName, $create = false, $hasNode = false)
    {
        $this->persistEvent->getOption('auto_name')->willReturn(true);
        $this->persistEvent->hasNode()->willReturn($hasNode);
        $node = $hasNode ? $this->node->reveal() : null;

        $this->document->getTitle()->willReturn($title);
        $this->document->getParent()->willReturn($this->parent);
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->slugifier->slugify($title)->willReturn($title);

        $this->resolver->resolveName($this->parentNode->reveal(), $title, $node)->willReturn($title);
        $this->persistEvent->getParentNode()->willReturn($this->parentNode->reveal());
        $this->strategy->createNodeForDocument($this->document->reveal(), $this->parentNode->reveal(), $expectedName)->willReturn($this->newNode->reveal());

        if (!$create) {
            return;
        }

        $this->persistEvent->setNode($this->newNode->reveal())->shouldBeCalled();
    }
}
