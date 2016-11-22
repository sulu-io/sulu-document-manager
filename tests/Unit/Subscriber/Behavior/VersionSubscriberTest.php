<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior;

use Jackalope\Workspace;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use PHPCR\Version\VersionManagerInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\Behavior\VersionBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Subscriber\Behavior\VersionSubscriber;

class VersionSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var Workspace
     */
    private $workspace;

    /**
     * @var VersionManagerInterface
     */
    private $versionManager;

    /**
     * @var \ReflectionProperty
     */
    private $checkoutPathsReflection;

    /**
     * @var \ReflectionProperty
     */
    private $checkpointPathsReflection;

    /**
     * @var VersionSubscriber
     */
    private $versionSubscriber;

    public function setUp()
    {
        $this->versionManager = $this->prophesize(VersionManagerInterface::class);
        $this->workspace = $this->prophesize(Workspace::class);
        $this->workspace->getVersionManager()->willReturn($this->versionManager->reveal());
        $this->session = $this->prophesize(SessionInterface::class);
        $this->session->getWorkspace()->willReturn($this->workspace->reveal());

        $this->versionSubscriber = new VersionSubscriber($this->session->reveal());

        $this->checkoutPathsReflection = new \ReflectionProperty(VersionSubscriber::class, 'checkoutPaths');
        $this->checkoutPathsReflection->setAccessible(true);

        $this->checkpointPathsReflection = new \ReflectionProperty(VersionSubscriber::class, 'checkpointPaths');
        $this->checkpointPathsReflection->setAccessible(true);
    }

    public function testSetVersionMixinOnPersist()
    {
        $event = $this->prophesize(PersistEvent::class);

        $document = $this->prophesize(VersionBehavior::class);
        $event->getDocument()->willReturn($document->reveal());

        $node = $this->prophesize(NodeInterface::class);
        $node->addMixin('mix:versionable')->shouldBeCalled();
        $event->getNode()->willReturn($node->reveal());

        $this->versionSubscriber->setVersionMixin($event->reveal());
    }

    public function testSetVersionMixinOnPersistWithoutVersionBehavior()
    {
        $event = $this->prophesize(PersistEvent::class);

        $event->getDocument()->willReturn(new \stdClass());

        $node = $this->prophesize(NodeInterface::class);
        $node->addMixin(Argument::any())->shouldNotBeCalled();
        $event->getNode()->willReturn($node->reveal());

        $this->versionSubscriber->setVersionMixin($event->reveal());
    }

    public function testSetVersionMixinOnPublish()
    {
        $event = $this->prophesize(PublishEvent::class);

        $document = $this->prophesize(VersionBehavior::class);
        $event->getDocument()->willReturn($document->reveal());

        $node = $this->prophesize(NodeInterface::class);
        $node->addMixin('mix:versionable')->shouldBeCalled();
        $event->getNode()->willReturn($node->reveal());

        $this->versionSubscriber->setVersionMixin($event->reveal());
    }

    public function testSetVersionMixinOnPublishWithoutVersionBehavior()
    {
        $event = $this->prophesize(PublishEvent::class);

        $event->getDocument()->willReturn(new \stdClass());

        $node = $this->prophesize(NodeInterface::class);
        $node->addMixin(Argument::any())->shouldNotBeCalled();
        $event->getNode()->willReturn($node->reveal());

        $this->versionSubscriber->setVersionMixin($event->reveal());
    }

    public function testRememberCheckoutNodes()
    {
        $event = $this->prophesize(PersistEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(VersionBehavior::class);

        $event->getNode()->willReturn($node->reveal());
        $event->getDocument()->willReturn($document->reveal());

        $node->getPath()->willReturn('/path/to/node');

        $this->versionSubscriber->rememberCheckoutPaths($event->reveal());

        $this->assertEquals(['/path/to/node'], $this->checkoutPathsReflection->getValue($this->versionSubscriber));
    }

    public function testRememberCheckoutNodesWithoutVersionBehavior()
    {
        $event = $this->prophesize(PersistEvent::class);
        $document = new \stdClass();

        $event->getDocument()->willReturn($document);

        $this->versionSubscriber->rememberCheckoutPaths($event->reveal());

        $this->assertEmpty($this->checkoutPathsReflection->getValue($this->versionSubscriber));
    }

    public function testRememberCreateVersionNodes()
    {
        $event = $this->prophesize(PublishEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(VersionBehavior::class)
            ->willImplement(LocaleBehavior::class);
        $document->getLocale()->willReturn('de');

        $event->getNode()->willReturn($node->reveal());
        $event->getDocument()->willReturn($document->reveal());

        $node->getPath()->willReturn('/path/to/node');

        $this->versionSubscriber->rememberCreateVersion($event->reveal());

        $this->assertEquals(
            [['path' => '/path/to/node', 'language' => 'de']],
            $this->checkpointPathsReflection->getValue($this->versionSubscriber)
        );
    }

    public function testRememberCreateVersionNodesWithoutVersionBehavior()
    {
        $event = $this->prophesize(PublishEvent::class);
        $document = new \stdClass();

        $event->getDocument()->willReturn($document);

        $this->versionSubscriber->rememberCreateVersion($event->reveal());
    }

    public function testApplyVersionOperations()
    {
        $this->checkoutPathsReflection->setValue($this->versionSubscriber, ['/node1', '/node2']);
        $this->checkpointPathsReflection->setValue($this->versionSubscriber, [['path' => '/node3', 'language' => 'de']]);

        $this->versionManager->isCheckedOut('/node1')->willReturn(false);
        $this->versionManager->isCheckedOut('/node2')->willReturn(true);

        $this->versionManager->checkout('/node1')->shouldBeCalled();
        $this->versionManager->checkout('/node2')->shouldNotBeCalled();

        $node = $this->prophesize(NodeInterface::class);
        $this->session->getNode('/node3')->willReturn($node->reveal());
        $this->versionManager->checkpoint('/node3')->shouldBeCalled();
        $node->getPropertyValueWithDefault('sulu:versions', [])->willReturn(['en']);
        $node->setProperty('sulu:versions', ['en', 'de'])->shouldBeCalled();
        $this->session->save()->shouldBeCalled();

        $this->versionSubscriber->applyVersionOperations();

        $this->assertEquals([], $this->checkpointPathsReflection->getValue($this->versionSubscriber));
        $this->assertEquals([], $this->checkoutPathsReflection->getValue($this->versionSubscriber));
    }
}
