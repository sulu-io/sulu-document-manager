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

use Jackalope\Version\Version as JackalopeVersion;
use Jackalope\Workspace;
use PHPCR\NodeInterface;
use PHPCR\NodeType\NodeDefinitionInterface;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use PHPCR\Version\OnParentVersionAction;
use PHPCR\Version\VersionHistoryInterface;
use PHPCR\Version\VersionInterface;
use PHPCR\Version\VersionManagerInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\Behavior\VersionBehavior;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Subscriber\Behavior\VersionSubscriber;
use Sulu\Component\DocumentManager\Version;
use Symfony\Bridge\PhpUnit\ClockMock;

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

    public function testSetVersionsOnDocument()
    {
        $event = $this->prophesize(HydrateEvent::class);

        $document = $this->prophesize(VersionBehavior::class);
        $event->getDocument()->willReturn($document->reveal());

        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValueWithDefault('sulu:versions', [])
            ->willReturn([
                '{"version":"1.0","locale":"de","author":null,"authored":"2016-12-06T09:37:21+01:00"}',
                '{"version":"1.1","locale":"en","author":1,"authored":"2016-12-05T19:47:22+01:00"}',
            ]);
        $event->getNode()->willReturn($node->reveal());

        $document->setVersions(
            [
                new Version('1.0', 'de', null, new \DateTime('2016-12-06T09:37:21+01:00')),
                new Version('1.1', 'en', 1, new \DateTime('2016-12-05T19:47:22+01:00')),
            ]
        );

        $this->versionSubscriber->setVersionsOnDocument($event->reveal());
    }

    public function testSetVersionsOnDocumentWithoutVersionBehavior()
    {
        $event = $this->prophesize(HydrateEvent::class);
        $event->getDocument()->willReturn(new \stdClass());
        $event->getNode()->shouldNotBeCalled();

        $this->versionSubscriber->setVersionsOnDocument($event->reveal());
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
        $event->getOption('user')->willReturn(2);

        $node->getPath()->willReturn('/path/to/node');

        $this->versionSubscriber->rememberCreateVersion($event->reveal());

        $this->assertEquals(
            [['path' => '/path/to/node', 'locale' => 'de', 'author' => 2]],
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
        ClockMock::register(VersionSubscriber::class);
        ClockMock::withClockMock(true);

        $this->checkoutPathsReflection->setValue($this->versionSubscriber, ['/node1', '/node2']);
        $this->checkpointPathsReflection->setValue($this->versionSubscriber, [
            ['path' => '/node3', 'locale' => 'de', 'author' => 1],
        ]);

        $this->versionManager->isCheckedOut('/node1')->willReturn(false);
        $this->versionManager->isCheckedOut('/node2')->willReturn(true);

        $this->versionManager->checkout('/node1')->shouldBeCalled();
        $this->versionManager->checkout('/node2')->shouldNotBeCalled();

        $node = $this->prophesize(NodeInterface::class);
        $this->session->getNode('/node3')->willReturn($node->reveal());

        $version = $this->prophesize(VersionInterface::class);
        $version->getName()->willReturn('a');
        $this->versionManager->checkpoint('/node3')->willReturn($version->reveal());
        $node->getPropertyValueWithDefault('sulu:versions', [])->willReturn([
            '{"locale":"en","version":"0","author":null,"authored":"2016-12-05T19:47:22+01:00"}',
        ]);
        $node->setProperty(
            'sulu:versions',
            [
                '{"locale":"en","version":"0","author":null,"authored":"2016-12-05T19:47:22+01:00"}',
                '{"locale":"de","version":"a","author":1,"authored":"' . date('c', ClockMock::time()) . '"}',
            ]
        )->shouldBeCalled();
        $this->session->save()->shouldBeCalled();

        $this->versionSubscriber->applyVersionOperations();

        $this->assertEquals([], $this->checkpointPathsReflection->getValue($this->versionSubscriber));
        $this->assertEquals([], $this->checkoutPathsReflection->getValue($this->versionSubscriber));

        ClockMock::withClockMock(false);
    }

    public function testApplyVersionOperationsWithMultipleCheckpoints()
    {
        $this->checkpointPathsReflection->setValue(
            $this->versionSubscriber,
            [
                ['path' => '/node1', 'locale' => 'de', 'author' => 2],
                ['path' => '/node1', 'locale' => 'en', 'author' => 3],
                ['path' => '/node2', 'locale' => 'en', 'author' => 1],
            ]
        );

        $node1 = $this->prophesize(NodeInterface::class);
        $node1->getPropertyValueWithDefault('sulu:versions', [])->willReturn(['{"locale":"fr","version":"0","author":1,"authored":"2016-12-05T19:47:22+01:00"}']);
        $this->session->getNode('/node1')->willReturn($node1->reveal());

        $node2 = $this->prophesize(NodeInterface::class);
        $node2->getPropertyValueWithDefault('sulu:versions', [])->willReturn(['{"locale":"en","version":"0","author":2,"authored":"2016-12-05T19:47:22+01:00"}']);
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
                '{"locale":"fr","version":"0","author":1,"authored":"2016-12-05T19:47:22+01:00"}',
                '{"locale":"de","version":"b","author":2,"authored":"' . date('c', ClockMock::time()) . '"}',
                '{"locale":"en","version":"b","author":3,"authored":"' . date('c', ClockMock::time()) . '"}',
            ]
        )->shouldBeCalled();
        $node2->setProperty(
            'sulu:versions',
            [
                '{"locale":"en","version":"0","author":2,"authored":"2016-12-05T19:47:22+01:00"}',
                '{"locale":"en","version":"c","author":1,"authored":"' . date('c', ClockMock::time()) . '"}',
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
        $version = $this->prophesize(JackalopeVersion::class);
        $frozenNode = $this->prophesize(NodeInterface::class);

        $node->getPath()->willReturn('/node');
        $property1 = $this->prophesize(PropertyInterface::class);
        $property1->getName()->willReturn('i18n:de-test');
        $property2 = $this->prophesize(PropertyInterface::class);
        $property2->getName()->willReturn('non-translatable-test');
        $property3 = $this->prophesize(PropertyInterface::class);
        $property3->getName()->willReturn('jcr:uuid');
        $node->getProperties()->willReturn([$property1->reveal(), $property2->reveal(), $property3->reveal()]);
        $node->getNodes()->willReturn([]);

        $property1->remove()->shouldBeCalled();
        $property2->remove()->shouldBeCalled();
        $property3->remove()->shouldNotBeCalled();

        $this->propertyEncoder->localizedContentName('', 'de')->willReturn('i18n:de-');
        $this->propertyEncoder->localizedSystemName('', 'de')->willReturn('i18n:de-');

        $frozenNode->getNodes()->willReturn([]);
        $frozenNode->getPropertiesValues()->willReturn([
            'i18n:de-test' => 'Title',
            'non-translatable-test' => 'Article',
            'jcr:uuid' => 'asdf',
        ]);

        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getVersion()->willReturn('1.0');
        $event->getLocale()->willReturn('de');

        $this->versionManager->getVersionHistory('/node')->willReturn($versionHistory->reveal());
        $versionHistory->getVersion('1.0')->willReturn($version->reveal());
        $version->getFrozenNode()->willReturn($frozenNode->reveal());

        $node->setProperty('i18n:de-test', 'Title')->shouldBeCalled();
        $node->setProperty('non-translatable-test', 'Article')->shouldBeCalled();
        $node->setProperty('jcr:uuid', 'asdf')->shouldNotBeCalled();

        $this->versionSubscriber->restoreProperties($event->reveal());
    }

    public function testRestoreChildren()
    {
        $event = $this->prophesize(RestoreEvent::class);
        $document = $this->prophesize(VersionBehavior::class);
        $node = $this->prophesize(NodeInterface::class);
        $versionHistory = $this->prophesize(VersionHistoryInterface::class);
        $version = $this->prophesize(JackalopeVersion::class);
        $frozenNode = $this->prophesize(NodeInterface::class);

        $node->getPath()->willReturn('/node');
        $node->getProperties()->willReturn([]);
        $node->hasNode('child1')->willReturn(false);
        $node->hasNode('child2')->willReturn(true);
        $node->hasNode('child3')->willReturn(true);

        $definition = $this->prophesize(NodeDefinitionInterface::class);
        $definition->getOnParentVersion()->willReturn(OnParentVersionAction::COPY);

        $newChild2Node = $this->prophesize(NodeInterface::class);
        $newChild2Node->getName()->willReturn('child2');
        $newChild2Node->getProperties()->willReturn([]);
        $newChild2Node->getNodes()->willReturn([]);
        $newChild2Node->getDefinition()->willReturn($definition->reveal());

        $newChild3Node = $this->prophesize(NodeInterface::class);
        $newChild3Node->getName()->willReturn('child3');
        $newChild3Node->getDefinition()->willReturn($definition->reveal());
        $newChild3Node->remove()->shouldBeCalled();

        $newChild1Node = $this->prophesize(NodeInterface::class);
        $newChild1Node->getName()->willReturn('child1');
        $newChild1Node->setMixins(['jcr:referencable'])->shouldBeCalled();
        $newChild1Node->getProperties()->willReturn([]);
        $newChild1Node->getNodes()->willReturn([]);
        $newChild1Node->getDefinition()->willReturn($definition->reveal());
        $node->addNode('child1')->will(
            function () use ($node, $newChild1Node, $newChild2Node, $newChild3Node) {
                $node->getNode('child1')->willReturn($newChild1Node->reveal());
                $node->getNodes()->willReturn(
                    [$newChild1Node->reveal(), $newChild2Node->reveal(), $newChild3Node->reveal()]
                );

                return $newChild1Node->reveal();
            }
        );

        $node->getNode('child2')->willReturn($newChild2Node->reveal());
        $node->getNode('child3')->willReturn($newChild3Node->reveal());
        $node->getNodes()->willReturn([$newChild2Node->reveal(), $newChild3Node->reveal()]);

        $this->propertyEncoder->localizedContentName('', 'de')->willReturn('i18n:de-');
        $this->propertyEncoder->localizedSystemName('', 'de')->willReturn('i18n:de-');

        $child1 = $this->prophesize(NodeInterface::class);
        $child1->getName()->willReturn('child1');
        $child1->getPropertyValueWithDefault('jcr:frozenMixinTypes', [])->willReturn(['jcr:referencable']);
        $child1->getPropertiesValues()->willReturn([]);
        $child1->getNodes()->willReturn([]);
        $child2 = $this->prophesize(NodeInterface::class);
        $child2->getName()->willReturn('child2');
        $child2->getPropertiesValues()->willReturn([]);
        $child2->getNodes()->willReturn([]);

        $frozenNode->getNodes()->willReturn([$child1->reveal(), $child2->reveal()]);
        $frozenNode->getPropertiesValues()->willReturn([]);
        $frozenNode->hasNode('child1')->willReturn(true);
        $frozenNode->hasNode('child2')->willReturn(true);
        $frozenNode->hasNode('child3')->willReturn(false);

        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getVersion()->willReturn('1.0');
        $event->getLocale()->willReturn('de');

        $this->versionManager->getVersionHistory('/node')->willReturn($versionHistory->reveal());
        $versionHistory->getVersion('1.0')->willReturn($version->reveal());
        $version->getFrozenNode()->willReturn($frozenNode->reveal());

        $this->versionSubscriber->restoreProperties($event->reveal());
    }
}
