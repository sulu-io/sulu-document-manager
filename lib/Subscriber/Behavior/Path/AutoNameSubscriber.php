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

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Behavior\Path\AutoNameBehavior;
use Sulu\Component\DocumentManager\DocumentHelper;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\DocumentStrategyInterface;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\DocumentManager\NameResolver;
use Sulu\Component\DocumentManager\NodeManager;
use Symfony\Cmf\Bundle\CoreBundle\Slugifier\SlugifierInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Automatically assign a name to the document based on its title.
 *
 * TODO: Refactor MOVE auto-name handling somehow.
 */
class AutoNameSubscriber implements EventSubscriberInterface
{
    /**
     * @var SlugifierInterface
     */
    private $slugifier;

    /**
     * @var NameResolver
     */
    private $resolver;

    /**
     * @var DocumentStrategyInterface
     */
    private $documentStrategy;

    /**
     * @param DocumentRegistry $registry
     * @param SlugifierInterface $slugifier
     * @param NameResolver $resolver
     * @param NodeManager $nodeManager
     * @param DocumentStrategyInterface $documentStrategy
     */
    public function __construct(
        SlugifierInterface $slugifier,
        NameResolver $resolver,
        DocumentStrategyInterface $documentStrategy
    ) {
        $this->slugifier = $slugifier;
        $this->resolver = $resolver;
        $this->documentStrategy = $documentStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::CONFIGURE_OPTIONS => 'configureOptions',
            Events::PERSIST => ['handlePersist', 480],
            Events::MOVE => ['handleMove', 480],
            Events::COPY => ['handleCopy', 480],
        ];
    }

    public function configureOptions(ConfigureOptionsEvent $event)
    {
        $event->getOptions()->setDefaults(
            [
                'auto_name' => true,
            ]
        );
    }

    /**
     * @param MoveEvent $event
     */
    public function handleMove(MoveEvent $event)
    {
        $this->handleMoveCopy($event);
    }

    /**
     * @param CopyEvent $event
     */
    public function handleCopy(CopyEvent $event)
    {
        $this->handleMoveCopy($event);
    }

    /**
     * @param PersistEvent $event
     *
     * @throws DocumentManagerException
     */
    public function handlePersist(PersistEvent $event)
    {
        if (!$event->getOption('auto_name')) {
            return;
        }

        $document = $event->getDocument();

        if (!$document instanceof AutoNameBehavior) {
            return;
        }

        $title = $document->getTitle();

        if (!$title) {
            throw new DocumentManagerException(
                sprintf(
                    'Document has no title (title is required for auto name behavior): %s)',
                    DocumentHelper::getDebugTitle($document)
                )
            );
        }

        $name = $this->slugifier->slugify($title);

        $parentNode = $event->getParentNode();

        $node = $event->hasNode() ? $event->getNode() : null;
        $name = $this->resolver->resolveName($parentNode, $name, $node);

        if (null === $node) {
            $node = $this->documentStrategy->createNodeForDocument($document, $parentNode, $name);
            $event->setNode($node);

            return;
        }

        if ($name === $node->getName()) {
            return;
        }

        $node = $event->getNode();
        $defaultLocale = $event->getContext()->getRegistry()->getDefaultLocale();

        if ($defaultLocale != $event->getLocale()) {
            return;
        }

        $this->rename($node, $name);
    }

    /**
     * TODO: This is a workaround for a bug in Jackalope which will be fixed in the next
     *       release 1.2: https://github.com/jackalope/jackalope/pull/262.
     */
    private function rename(NodeInterface $node, $name)
    {
        $names = (array) $node->getParent()->getNodeNames();
        $pos = array_search($node->getName(), $names);
        $next = isset($names[$pos + 1]) ? $names[$pos + 1] : null;

        $node->rename($name);

        if ($next) {
            $node->getParent()->orderBefore($name, $next);
        }
    }

    /**
     * Resolve the destination name on move and copy events.
     *
     * @param MoveEvent $event
     */
    private function handleMoveCopy(MoveEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof AutoNameBehavior) {
            return;
        }

        $destId = $event->getDestId();
        $node = $event->getContext()->getRegistry()->getNodeForDocument($document);
        $destNode = $event->getContext()->getNodeManager()->find($destId);
        $nodeName = $this->resolver->resolveName($destNode, $node->getName());

        $event->setDestName($nodeName);
    }
}
