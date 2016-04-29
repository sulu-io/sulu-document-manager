<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Core;

use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Responsible for registering and deregistering documents and PHPCR nodes
 * with the Document Registry.
 */
class RegistratorSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => [
                ['handleDefaultLocale', 520],
                ['handleDocumentFromRegistry', 510],
                ['handleStopPropagationAndResetLocale', 509],
                ['handleHydrate', 490],
                ['handleEndHydrate', -500],
            ],
            Events::PERSIST => [
                ['handlePersist', 450],
                ['handleNodeFromRegistry', 510],
                ['handleEndPersist', -500],
            ],
            Events::REMOVE => ['handleRemove', 490],
            Events::CLEAR => ['handleClear', 500],
            Events::REORDER => ['handleNodeFromRegistry', 510],
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
            'rehydrate' => true,
        ]);
    }

    /**
     * Set the default locale for the hydration request.
     *
     * @param HydrateEvent $event
     */
    public function handleDefaultLocale(HydrateEvent $event)
    {
        // set the default locale
        if (null === $event->getLocale()) {
            $event->setLocale($event->getRegistry()->getDefaultLocale());
        }
    }

    /**
     * If there is already a document for the node registered, use that.
     *
     * @param HydrateEvent $event
     */
    public function handleDocumentFromRegistry(HydrateEvent $event)
    {
        if ($event->hasDocument()) {
            return;
        }

        $node = $event->getNode();

        if (!$event->getRegistry()->hasNode($node)) {
            return;
        }

        $document = $event->getRegistry()->getDocumentForNode($node);

        $event->setDocument($document);

        $options = $event->getOptions();

        // if reydration is not required (f.e. we just want to retrieve the
        // current state of the document, no matter it's current state) stop
        // further event propagation - we have the document now.
        if (isset($options['rehydrate']) && false === $options['rehydrate']) {
            $event->stopPropagation();
        }
    }

    /**
     * Stop propagation if the document is already loaded in the requested locale,
     * otherwise reset the document locale to the new locale.
     *
     * @param HydrateEvent $event
     */
    public function handleStopPropagationAndResetLocale(HydrateEvent $event)
    {
        if (!$event->hasDocument()) {
            return;
        }

        $locale = $event->getLocale();
        $document = $event->getDocument();
        $options = $event->getOptions();
        $originalLocale = $event->getRegistry()->getOriginalLocaleForDocument($document);

        if (
            (!isset($options['rehydrate']) || false === $options['rehydrate']) &&
            (true === $event->getRegistry()->isHydrated($document) && $originalLocale === $locale)
        ) {
            $event->stopPropagation();

            return;
        }

        $event->getRegistry()->updateLocale($document, $locale, $locale);
    }

    /**
     * When the hydrate request has finished, mark the document has hydrated.
     * This should be the last event listener called.
     *
     * @param HydrateEvent $event
     */
    public function handleEndHydrate(HydrateEvent $event)
    {
        $event->getRegistry()->markDocumentAsHydrated($event->getDocument());
    }

    /**
     * After the persist event has ended, unmark the document from being hydrated so that
     * it will be re-hydrated on the next request.
     *
     * TODO: There might be better ways to ensure that the document state is updated.
     *
     * @param PersistEvent $event
     */
    public function handleEndPersist(PersistEvent $event)
    {
        $event->getRegistry()->unmarkDocumentAsHydrated($event->getDocument());
    }

    /**
     * If the node for the persisted document is in the registry.
     *
     * @param PersistEvent|ReorderEvent $event
     */
    public function handleNodeFromRegistry($event)
    {
        if ($event->hasNode()) {
            return;
        }

        $document = $event->getDocument();

        if (!$event->getRegistry()->hasDocument($document)) {
            return;
        }

        $node = $event->getRegistry()->getNodeForDocument($document);
        $event->setNode($node);
    }

    /**
     * Register any document that has been created in the hydrate event.
     *
     * @param HydrateEvent $event
     */
    public function handleHydrate(HydrateEvent $event)
    {
        $this->handleRegister($event);
    }

    /**
     * Register any document that has been created in the persist event.
     *
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $this->handleRegister($event);
    }

    /**
     * Deregister removed documents.
     *
     * @param RemoveEvent $event
     */
    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();
        $event->getRegistry()->deregisterDocument($document);
    }

    /**
     * Clear the register on the "clear" event.
     *
     * @param ClearEvent $event
     */
    public function handleClear(ClearEvent $event)
    {
        $event->getRegistry()->clear();
    }

    /**
     * Register the document and apparently update the locale.
     *
     * TODO: Is locale handling already done above?
     *
     * @param AbstractMappingEvent $event
     */
    private function handleRegister(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        $node = $event->getNode();
        $locale = $event->getLocale();
        $registry = $event->getRegistry();

        if ($registry->hasDocument($document)) {
            $registry->updateLocale($document, $locale);

            return;
        }

        $registry->registerDocument($document, $node, $locale);
    }
}
