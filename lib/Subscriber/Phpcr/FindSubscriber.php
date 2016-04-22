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

use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\FindEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\NodeManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Exception\InvalidArgumentException;
use Sulu\Component\DocumentManager\Exception\RuntimeException;

/**
 * This class is responsible for finding documents.
 */
class FindSubscriber implements EventSubscriberInterface
{
    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @param MetadataFactoryInterface $metadataFactory
     * @param NodeManager $nodeManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        MetadataFactoryInterface $metadataFactory
    ) {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::FIND => ['handleFind', 500],
            Events::CONFIGURE_OPTIONS => 'configureOptions',
        ];
    }

    /**
     * @param ConfigureOptionsEvent $event
     */
    public function configureOptions(ConfigureOptionsEvent $event)
    {
        $options = $event->getOptions();
        $options->setDefaults([
            'type' => null,
        ]);
    }

    /**
     * @param FindEvent $event
     *
     * @throws DocumentManagerException
     * @throws DocumentNotFoundException
     */
    public function handleFind(FindEvent $event)
    {
        $options = $event->getOptions();
        $aliasOrClass = $options['type'];
        $manager = $event->getManager();
        $node = $event->getManager()->getNodeManager()->find($event->getId());

        $hydrateEvent = new HydrateEvent($manager, $node, $event->getLocale(), $options);
        $manager->getEventDispatcher()->dispatch(Events::HYDRATE, $hydrateEvent);
        $document = $hydrateEvent->getDocument();

        if ($aliasOrClass) {
            $this->checkAliasOrClass($aliasOrClass, $document);
        }

        $event->setDocument($hydrateEvent->getDocument());
    }

    private function checkAliasOrClass($aliasOrClass, $document)
    {
        if ($this->metadataFactory->hasAlias($aliasOrClass)) {
            $class = $this->metadataFactory->getMetadataForAlias($aliasOrClass)->getClass();
        } elseif (!class_exists($aliasOrClass)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown class specified and no alias exists for "%s", known aliases: "%s"',
                $aliasOrClass, implode('", "', $this->metadataFactory->getAliases())
            ));
        } else {
            $class = $aliasOrClass;
        }

        if (get_class($document) !== $class) {
            throw new DocumentNotFoundException(sprintf(
                'Requested document of type "%s" but got document of type "%s"',
                $aliasOrClass,
                get_class($document)
            ));
        }
    }
}
