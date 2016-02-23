<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping;

use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Maps the locale.
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => ['handleLocale', 250],
            Events::PERSIST => ['handleLocale', 250],
        ];
    }

    /**
     * @param AbstractMappingEvent $event
     *
     * @throws DocumentManagerException
     */
    public function handleLocale(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof LocaleBehavior) {
            return;
        }

        $event->getAccessor()->set(
            'locale',
            $event->getContext()->getRegistry()->getLocaleForDocument($document)
        );
    }
}
