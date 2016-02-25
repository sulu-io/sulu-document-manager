<?php

namespace Sulu\Component\DocumentManager;

use Sulu\Component\DocumentManager\InspectorFactoryInterface;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use Sulu\Component\DocumentManager\DocumentManagerContext;

/**
 * Document inspector factory.
 */
class DocumentInspectorFactory implements DocumentInspectorFactoryInterface 
{
    /**
     * @var PathSegmentRegistry
     */
    private $pathSegmentRegistry;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    public function __construct(PathSegmentRegistry $pathSegmentRegistry)
    {
        $this->pathSegmentRegistry = $pathSegmentRegistry;
    }

    public function getInspector(DocumentManagerContext $context)
    {
        if ($this->inspector) {
            return $this->inspector;
        }

        $this->inspector = new DocumentInspector(
            $context->getRegistry(),
            $this->pathSegmentRegistry,
            $context->getProxyFactory()
        );

        return $this->inspector;
    }
}
