<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Phpcr;

use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NodeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Remove subscriber.
 */
class RemoveSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    public function __construct(
        DocumentRegistry $documentRegistry,
        NodeManager $nodeManager
    ) {
        $this->documentRegistry = $documentRegistry;
        $this->nodeManager = $nodeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::CONFIGURE_OPTIONS => ['configureOptions', 0],
            Events::REMOVE => ['handleRemove', 500],
        ];
    }

    /**
     * Adds the options for this subscriber to the OptionsResolver
     *
     * @param ConfigureOptionsEvent $event
     */
    public function configureOptions(ConfigureOptionsEvent $event)
    {
        $options = $event->getOptions();

        $options->setDefault('dereference', false);
        $options->addAllowedTypes('dereference', 'bool');
    }

    /**
     * Remove the given documents node from PHPCR session and optionally
     * remove any references to the node.
     *
     * @param RemoveEvent $event
     */
    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();
        $node = $this->documentRegistry->getNodeForDocument($document);

        $node->remove();
    }
}
