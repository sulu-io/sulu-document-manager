<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior;

use Jackalope\Version\VersionManager;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Component\DocumentManager\Behavior\VersionBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Version;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber is responsible for creating versions of a document.
 */
class VersionSubscriber implements EventSubscriberInterface
{
    const VERSION_PROPERTY = 'sulu:versions';

    /**
     * @var SessionInterface
     */
    private $defaultSession;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var string[]
     */
    private $checkoutPaths = [];

    /**
     * @var string[]
     */
    private $checkpointPaths = [];

    public function __construct(SessionInterface $defaultSession, PropertyEncoder $propertyEncoder)
    {
        $this->defaultSession = $defaultSession;
        $this->propertyEncoder = $propertyEncoder;
        $this->versionManager = $defaultSession->getWorkspace()->getVersionManager();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => [
                ['setVersionMixin', 468],
                ['rememberCheckoutPaths'],
            ],
            Events::PUBLISH => [
                ['setVersionMixin', 468],
                ['rememberCreateVersion'],
            ],
            Events::HYDRATE => 'setVersionsOnDocument',
            Events::FLUSH => 'applyVersionOperations',
            Events::RESTORE => 'restoreLocalizedProperties',
        ];
    }

    /**
     * Sets the versionable mixin on the node if it is a versionable document.
     *
     * @param AbstractMappingEvent $event
     */
    public function setVersionMixin(AbstractMappingEvent $event)
    {
        if (!$this->support($event->getDocument())) {
            return;
        }

        $event->getNode()->addMixin('mix:versionable');
    }

    /**
     * Sets the version information set on the node to the document.
     *
     * @param HydrateEvent $event
     */
    public function setVersionsOnDocument(HydrateEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->support($document)) {
            return;
        }

        $node = $event->getNode();

        $versions = [];
        $versionProperty = $node->getPropertyValueWithDefault(static::VERSION_PROPERTY, []);
        foreach ($versionProperty as $version) {
            $versionInformation = json_decode($version);
            $versions[] = new Version(
                $versionInformation->version,
                $versionInformation->locale,
                $versionInformation->author
            );
        }

        $document->setVersions($versions);
    }

    /**
     * Remember which paths need to be checked out after everything has been saved.
     *
     * @param PersistEvent $event
     */
    public function rememberCheckoutPaths(PersistEvent $event)
    {
        if (!$this->support($event->getDocument())) {
            return;
        }

        $this->checkoutPaths[] = $event->getNode()->getPath();
    }

    /**
     * Remember for which paths a new version has to be created.
     *
     * @param PublishEvent $event
     */
    public function rememberCreateVersion(PublishEvent $event)
    {
        $document = $event->getDocument();
        if (!$this->support($document)) {
            return;
        }

        $this->checkpointPaths[] = [
            'path' => $event->getNode()->getPath(),
            'locale' => $document->getLocale(),
            'author' => $event->getOption('user'),
        ];
    }

    /**
     * Apply all the operations which have been remembered after the flush.
     */
    public function applyVersionOperations()
    {
        foreach ($this->checkoutPaths as $path) {
            if (!$this->versionManager->isCheckedOut($path)) {
                $this->versionManager->checkout($path);
            }
        }

        $this->checkoutPaths = [];

        /** @var NodeInterface[] $nodes */
        $nodes = [];
        $nodeVersions = [];
        foreach ($this->checkpointPaths as $versionInformation) {
            $version = $this->versionManager->checkpoint($versionInformation['path']);

            if (!array_key_exists($versionInformation['path'], $nodes)) {
                $nodes[$versionInformation['path']] = $this->defaultSession->getNode($versionInformation['path']);
            }
            $versions = $nodes[$versionInformation['path']]->getPropertyValueWithDefault(static::VERSION_PROPERTY, []);

            if (!array_key_exists($versionInformation['path'], $nodeVersions)) {
                $nodeVersions[$versionInformation['path']] = $versions;
            }
            $nodeVersions[$versionInformation['path']][] = json_encode([
                'locale' => $versionInformation['locale'],
                'version' => $version->getName(),
                'author' => $versionInformation['author'],
            ]);
        }

        foreach ($nodes as $path => $node) {
            $node->setProperty(static::VERSION_PROPERTY, $nodeVersions[$path]);
        }

        $this->defaultSession->save();
        $this->checkpointPaths = [];
    }

    /**
     * Restore the localized properties of the old version.
     *
     * @param RestoreEvent $event
     */
    public function restoreLocalizedProperties(RestoreEvent $event)
    {
        if (!$this->support($event->getDocument())) {
            $event->stopPropagation();

            return;
        }

        $contentPropertyFilter = $this->propertyEncoder->localizedContentName('*', $event->getLocale());
        $systemPropertyFilter = $this->propertyEncoder->localizedSystemName('*', $event->getLocale());

        $node = $event->getNode();

        foreach ($node->getProperties($contentPropertyFilter) as $contentProperty) {
            $contentProperty->remove();
        }

        foreach ($node->getProperties($systemPropertyFilter) as $systemProperty) {
            $systemProperty->remove();
        }

        $version = $this->versionManager->getVersionHistory($node->getPath())->getVersion($event->getVersion());
        $frozenNode = $version->getFrozenNode();

        foreach ($frozenNode->getPropertiesValues($contentPropertyFilter) as $name => $value) {
            $node->setProperty($name, $value);
        }

        foreach ($frozenNode->getPropertiesValues($systemPropertyFilter) as $name => $value) {
            $node->setProperty($name, $value);
        }
    }

    /**
     * Determines if the given document supports versioning.
     *
     * @param $document
     *
     * @return bool
     */
    private function support($document)
    {
        return $document instanceof VersionBehavior;
    }
}
