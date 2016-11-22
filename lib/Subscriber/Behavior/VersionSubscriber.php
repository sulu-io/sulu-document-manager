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
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Events;
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

    public function __construct(SessionInterface $defaultSession)
    {
        $this->defaultSession = $defaultSession;
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
            Events::FLUSH => 'applyVersionOperations',
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

        $this->checkpointPaths[] = ['path' => $event->getNode()->getPath(), 'language' => $document->getLocale()];
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
            $this->versionManager->checkpoint($versionInformation['path']);

            if (!array_key_exists($versionInformation['path'], $nodes)) {
                $nodes[$versionInformation['path']] = $this->defaultSession->getNode($versionInformation['path']);
            }
            $versions = $nodes[$versionInformation['path']]->getPropertyValueWithDefault(static::VERSION_PROPERTY, []);

            if (!array_key_exists($versionInformation['path'], $nodeVersions)) {
                $nodeVersions[$versionInformation['path']] = $versions;
            }
            $nodeVersions[$versionInformation['path']][] = $versionInformation['language'];
        }

        foreach ($nodes as $path => $node) {
            $node->setProperty(static::VERSION_PROPERTY, $nodeVersions[$path]);
        }

        $this->defaultSession->save();
        $this->checkpointPaths = [];
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
