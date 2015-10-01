<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Audit;

use Sulu\Component\DocumentManager\Behavior\Audit\BlameBehavior;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Manages user blame (log who creator the document and who updated it last).
 */
class BlameSubscriber implements EventSubscriberInterface
{
    const CREATOR = 'creator';
    const CHANGER = 'changer';

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @param TokenStorage $tokenStorage
     */
    public function __construct(TokenStorage $tokenStorage = null)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::CONFIGURE_OPTIONS => 'configureOptions',
            Events::PERSIST => 'handlePersist',
            Events::METADATA_LOAD => 'handleMetadataLoad',
        ];
    }

    /**
     * @param ConfigureOptionsEvent $event
     */
    public function configureOptions(ConfigureOptionsEvent $event)
    {
        $event->getOptions()->setDefaults([
            'user' => null,
        ]);
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (!$metadata->getReflectionClass()->isSubclassOf(BlameBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping('creator', [
            'encoding' => 'system_localized',
            'type' => 'date',
        ]);
        $metadata->addFieldMapping('changer', [
            'encoding' => 'system_localized',
            'type' => 'date',
        ]);
    }

    /**
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof BlameBehavior) {
            return;
        }

        $userId = $this->getUserId($event->getOptions());

        if (null === $userId) {
            return;
        }

        if (!$event->getLocale()) {
            return;
        }

        if (!$document->getCreator()) {
            $event->getAccessor()->set(self::CREATOR, $userId);
        }

        $event->getAccessor()->set(self::CHANGER, $userId);
    }

    private function getUserId($options)
    {
        if ($options['user']) {
            return $options['user'];
        }

        if (null === $this->tokenStorage) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token || $token instanceof AnonymousToken) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            throw new \InvalidArgumentException(sprintf(
                'User must implement the Sulu UserInterface, got "%s"',
                is_object($user) ? get_class($user) : gettype($user)
            ));
        }

        return $user->getId();
    }
}
