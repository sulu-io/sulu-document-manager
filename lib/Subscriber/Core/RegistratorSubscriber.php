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

use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Responsible for registering and deregistering documents and PHPCR nodes
 * with the Document Registry.
 */
class RegistratorSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @param DocumentRegistry $documentRegistry
     */
    public function __construct(
        DocumentRegistry $documentRegistry
    ) {
        $this->documentRegistry = $documentRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::HYDRATE => array(
                array('handleDefaultLocaleHydrate', 520),
                array('handleDocumentFromRegistry', 510),
                array('handleStopPropagationAndResetLocale', 509),
                array('handleHydrate', 490),
                array('handleEndHydrate', -500),
            ),
            Events::PERSIST => array(
                array('handleDefaultLocalePersist', 520),
                array('handlePersist', 450),
                array('handleNodeFromRegistry', 510),
                array('handleEndPersist', -500),
            ),
            Events::REMOVE => array('handleRemove', 490),
            Events::CLEAR => array('handleClear', 500),
        );
    }

    /**
     * Set the default locale for the hydration request.
     *
     * @param HydrateEvent $event
     */
    public function handleDefaultLocaleHydrate(HydrateEvent $event)
    {
        if (null === $event->getLocale()) {
            $event->setLocale($this->documentRegistry->getDefaultLocale());
        }
    }

    /**
     * Set the default locale for the persist request.
     *
     * @param HydrateEvent $event
     */
    public function handleDefaultLocalePersist(PersistEvent $event)
    {
        if (null === $event->getLocale()) {
            $event->setLocale($this->documentRegistry->getDefaultLocale());
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

        if (!$this->documentRegistry->hasNode($node)) {
            return;
        }

        $document = $this->documentRegistry->getDocumentForNode($node);

        $event->setDocument($document);
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
        $originalLocale = $this->documentRegistry->getOriginalLocaleForDocument($document);

        if (true === $this->documentRegistry->isHydrated($document) && $originalLocale === $locale) {
            $event->stopPropagation();

            return;
        }

        $this->documentRegistry->updateLocale($document, $locale, $locale);
    }

    /**
     * When the hydrate request has finished, mark the document has hydrated.
     * This should be the last event listener called.
     *
     * @param HydrateEvent $event
     */
    public function handleEndHydrate(HydrateEvent $event)
    {
        $this->documentRegistry->markDocumentAsHydrated($event->getDocument());
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
        $this->documentRegistry->unmarkDocumentAsHydrated($event->getDocument());
    }

    /**
     * If the node for the persisted document is in the registry.
     *
     * @param PersistEvent $event
     */
    public function handleNodeFromRegistry(PersistEvent $event)
    {
        if ($event->hasNode()) {
            return;
        }

        $document = $event->getDocument();

        if (!$this->documentRegistry->hasDocument($document)) {
            return;
        }

        $node = $this->documentRegistry->getNodeForDocument($document);
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
        $this->documentRegistry->deregisterDocument($document);
    }

    /**
     * Clear the register on the "clear" event.
     *
     * @param ClearEvent $event
     */
    public function handleClear(ClearEvent $event)
    {
        $this->documentRegistry->clear();
    }

    /*
     * Register the document and apparently update the locale --
     *
     * TODO: Is locale handling already done above??
     *
     * @param AbstractMappingEvent $event
     */
    private function handleRegister(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        $node = $event->getNode();
        $locale = $event->getLocale();

        if ($this->documentRegistry->hasDocument($document)) {
            $this->documentRegistry->updateLocale($document, $locale);

            return;
        }

        $this->documentRegistry->registerDocument($document, $node, $locale);
    }
}
