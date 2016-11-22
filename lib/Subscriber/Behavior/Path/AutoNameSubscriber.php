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
     * @var DocumentRegistry
     */
    private $registry;

    /**
     * @var SlugifierInterface
     */
    private $slugifier;

    /**
     * @var NameResolver
     */
    private $resolver;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @param DocumentRegistry $registry
     * @param SlugifierInterface $slugifier
     * @param NameResolver $resolver
     * @param NodeManager $nodeManager
     */
    public function __construct(
        DocumentRegistry $registry,
        SlugifierInterface $slugifier,
        NameResolver $resolver,
        NodeManager $nodeManager
    ) {
        $this->registry = $registry;
        $this->slugifier = $slugifier;
        $this->resolver = $resolver;
        $this->nodeManager = $nodeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::CONFIGURE_OPTIONS => 'configureOptions',
            Events::PERSIST => [
                ['handlePersist', 480],
                // the rename has to done at the very end to avoid ItemNotFoundException
                // see https://github.com/jackalope/jackalope/blob/3cf6e0582acb26b5b83b3445be238ea8aadf46ec/src/Jackalope/ObjectManager.php#L429
                ['handleRename', -480],
            ],
            Events::MOVE => ['handleMove', 480],
            Events::COPY => ['handleCopy', 480],
        ];
    }

    public function configureOptions(ConfigureOptionsEvent $event)
    {
        $options = $event->getOptions();

        $options->setDefaults(
            [
                'auto_name' => true,
                'auto_rename' => true,
                'auto_name_locale' => $this->registry->getDefaultLocale(),
            ]
        );

        $options->setAllowedTypes('auto_name', 'bool');
        $options->setAllowedTypes('auto_rename', 'bool');
        $options->setAllowedTypes('auto_name_locale', 'string');
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
        $document = $event->getDocument();
        if (!$event->getOption('auto_name') || !$document instanceof AutoNameBehavior || $event->hasNode()) {
            return;
        }

        $parentNode = $event->getParentNode();
        $name = $this->getName($document, $parentNode, $event->getOption('auto_rename'));
        $node = $parentNode->addNode($name);
        $event->setNode($node);
    }

    /**
     * Renames node if necessary.
     *
     * @param PersistEvent $event
     */
    public function handleRename(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$event->getOption('auto_name')
            || !$document instanceof AutoNameBehavior
            || $event->getOption('auto_name_locale') !== $event->getLocale()
            || !$event->hasNode()
            || $event->getNode()->isNew()
        ) {
            return;
        }

        $node = $event->getNode();
        $name = $this->getName($document, $event->getParentNode(), $event->getOption('auto_rename'), $node);

        if ($name === $node->getName()) {
            return;
        }

        $this->rename($event->getNode(), $name);
    }

    /**
     * Returns unique name for given document and nodes.
     *
     * @param AutoNameBehavior $document
     * @param NodeInterface $parentNode
     * @param NodeInterface|null $node
     * @param bool $autoRename
     *
     * @return string
     *
     * @throws DocumentManagerException
     */
    private function getName(
        AutoNameBehavior $document,
        NodeInterface $parentNode,
        $autoRename = true,
        NodeInterface $node = null
    ) {
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

        // jackrabbit can not handle node-names which contains a number followed by "e" e.g. 10e
        $name = preg_replace('((\d+)([eE]))', '$1-$2', $name);

        return $this->resolver->resolveName($parentNode, $name, $node, $autoRename);
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
        $node = $this->registry->getNodeForDocument($document);
        $destNode = $this->nodeManager->find($destId);
        $nodeName = $this->resolver->resolveName($destNode, $node->getName());

        $event->setDestName($nodeName);
    }
}
