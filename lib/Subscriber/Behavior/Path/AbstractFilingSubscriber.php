<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Path;

use Doctrine\Common\Util\Inflector;
use Sulu\Component\DocumentManager\ClassNameInflector;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Metadata\BaseMetadataFactory;
use Sulu\Component\DocumentManager\NodeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Automatically set the parent at a pre-determined location.
 */
abstract class AbstractFilingSubscriber implements EventSubscriberInterface
{
    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var BaseMetadataFactory
     */
    private $baseMetadataFactory;

    /**
     * @param NodeManager $nodeManager
     * @param DocumentManagerInterface $documentManager
     * @param string $basePath
     */
    public function __construct(
        NodeManager $nodeManager,
        DocumentManagerInterface $documentManager,
        BaseMetadataFactory $baseMetadataFactory,
        $basePath
    ) {
        $this->nodeManager = $nodeManager;
        $this->documentManager = $documentManager;
        $this->basePath = $basePath;
        $this->baseMetadataFactory = $baseMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => ['handlePersist', 490],
        ];
    }

    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $alias = $this->baseMetadataFactory->getMetadataForClass(get_class($document))->getAlias();

        $parentName = $this->getParentName($document);
        $path = sprintf('%s/%s/%s', $this->basePath, Inflector::pluralize($alias) , $parentName);

        $parentNode = $this->nodeManager->createPath($path);
        $event->setParentNode($parentNode);
    }

    /**
     * Return true if this subscriber should be applied to the document.
     *
     * @param object $document
     */
    abstract protected function supports($document);

    /**
     * Return the name of the parent document.
     *
     * @param $document
     *
     * @return string
     */
    abstract protected function getParentName($document);
}
