<?php

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Path;

use Sulu\Component\DocumentManager\Behavior\Path\ResetFilingPathBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NodeManager;

/**
 * Resets the path to base path.
 *
 * this is used for example with the AliasFilingBehavior
 */
class ResetFilingPathSubscriber extends AbstractFilingSubscriber
{
    /**
     * @var string
     */
    protected $basePath;

    /**
     * @param NodeManager $nodeManager
     * @param string $basePath
     */
    public function __construct(
        NodeManager $nodeManager,
        $basePath
    ) {
        parent::__construct($nodeManager);
        $this->basePath = $basePath;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => ['handlePersist', 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function generatePath(PersistEvent $event)
    {
        return $this->basePath;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($document)
    {
        return $document instanceof ResetFilingPathBehavior;
    }

    /**
     * {@inheritdoc}
     */
    protected function getParentName($document)
    {
        return '';
    }
}
