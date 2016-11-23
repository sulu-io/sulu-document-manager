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

use Jackalope\Version\Version;
use Jackalope\Workspace;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use PHPCR\Version\VersionHistoryInterface;
use PHPCR\Version\VersionInterface;
use PHPCR\Version\VersionManagerInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\Behavior\VersionBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;
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
     * @var PropertyEncoder
     */
    private $propertyEncoder;

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
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->workspace = $this->prophesize(Workspace::class);
        $this->workspace->getVersionManager()->willReturn($this->versionManager->reveal());
        $this->session = $this->prophesize(SessionInterface::class);
        $this->session->getWorkspace()->willReturn($this->workspace->reveal());

        $this->versionSubscriber = new VersionSubscriber($this->session->reveal(), $this->propertyEncoder->reveal());

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
            [['path' => '/path/to/node', 'locale' => 'de']],
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
        $this->checkpointPathsReflection->setValue($this->versionSubscriber, [['path' => '/node3', 'locale' => 'de']]);

        $this->versionManager->isCheckedOut('/node1')->willReturn(false);
        $this->versionManager->isCheckedOut('/node2')->willReturn(true);

        $this->versionManager->checkout('/node1')->shouldBeCalled();
        $this->versionManager->checkout('/node2')->shouldNotBeCalled();

        $node = $this->prophesize(NodeInterface::class);
        $this->session->getNode('/node3')->willReturn($node->reveal());

        $version = $this->prophesize(VersionInterface::class);
        $version->getName()->willReturn('a');
        $this->versionManager->checkpoint('/node3')->willReturn($version->reveal());
        $node->getPropertyValueWithDefault('sulu:versions', [])->willReturn(['{"locale":"en","version":"0"}']);
        $node->setProperty(
            'sulu:versions',
            [
                '{"locale":"en","version":"0"}',
                '{"locale":"de","version":"a"}',
            ]
        )->shouldBeCalled();
        $this->session->save()->shouldBeCalled();

        $this->versionSubscriber->applyVersionOperations();

        $this->assertEquals([], $this->checkpointPathsReflection->getValue($this->versionSubscriber));
        $this->assertEquals([], $this->checkoutPathsReflection->getValue($this->versionSubscriber));
    }

    public function testApplyVersionOperationsWithMultipleCheckpoints()
    {
        $this->checkpointPathsReflection->setValue(
            $this->versionSubscriber,
            [
                ['path' => '/node1', 'locale' => 'de'],
                ['path' => '/node1', 'locale' => 'en'],
                ['path' => '/node2', 'locale' => 'en'],
            ]
        );

        $node1 = $this->prophesize(NodeInterface::class);
        $node1->getPropertyValueWithDefault('sulu:versions', [])->willReturn(['{"locale":"fr","version":"0"}']);
        $this->session->getNode('/node1')->willReturn($node1->reveal());

        $node2 = $this->prophesize(NodeInterface::class);
        $node2->getPropertyValueWithDefault('sulu:versions', [])->willReturn(['{"locale":"en","version":"0"}']);
        $this->session->getNode('/node2')->willReturn($node2->reveal());

        $version1 = $this->prophesize(VersionInterface::class);
        $version2 = $this->prophesize(VersionInterface::class);
        $version3 = $this->prophesize(VersionInterface::class);
        $this->versionManager->checkpoint('/node1')->willReturn($version1->reveal());
        $this->versionManager->checkpoint('/node1')->willReturn($version2->reveal());
        $this->versionManager->checkpoint('/node2')->willReturn($version3->reveal());

        $version1->getName()->willReturn('a');
        $version2->getName()->willReturn('b');
        $version3->getName()->willReturn('c');

        $this->session->save()->shouldBeCalledTimes(1);
        $node1->setProperty(
            'sulu:versions',
            [
                '{"locale":"fr","version":"0"}',
                '{"locale":"de","version":"b"}',
                '{"locale":"en","version":"b"}',
            ]
        )->shouldBeCalled();
        $node2->setProperty(
            'sulu:versions',
            [
                '{"locale":"en","version":"0"}',
                '{"locale":"en","version":"c"}',
            ]
        )->shouldBeCalled();

        $this->versionSubscriber->applyVersionOperations();
    }

    public function testRestoreLocalizedProperties()
    {
        $event = $this->prophesize(RestoreEvent::class);
        $document = $this->prophesize(VersionBehavior::class);
        $node = $this->prophesize(NodeInterface::class);
        $versionHistory = $this->prophesize(VersionHistoryInterface::class);
        $version = $this->prophesize(Version::class);
        $frozenNode = $this->prophesize(NodeInterface::class);

        $node->getPath()->willReturn('/node');
        $property1 = $this->prophesize(PropertyInterface::class);
        $property2 = $this->prophesize(PropertyInterface::class);
        $node->getProperties('i18n:de-*')->willReturn([$property1->reveal(), $property2->reveal()]);

        $property1->remove()->shouldBeCalled();
        $property2->remove()->shouldBeCalled();

        $this->propertyEncoder->localizedContentName('*', 'de')->willReturn('i18n:de-*');
        $this->propertyEncoder->localizedSystemName('*', 'de')->willReturn('i18n:de-*');

        $frozenNode->getPropertiesValues('i18n:de-*')->willReturn([
            'i18n:de-title' => 'Title',
            'i18n:de-article' => 'Article',
        ]);

        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getVersion()->willReturn('1.0');
        $event->getLocale()->willReturn('de');

        $this->versionManager->getVersionHistory('/node')->willReturn($versionHistory->reveal());
        $versionHistory->getVersion('1.0')->willReturn($version->reveal());
        $version->getFrozenNode()->willReturn($frozenNode->reveal());

        $node->setProperty('i18n:de-title', 'Title')->shouldBeCalled();
        $node->setProperty('i18n:de-article', 'Article')->shouldBeCalled();

        $this->versionSubscriber->restoreLocalizedProperties($event->reveal());
    }
}
