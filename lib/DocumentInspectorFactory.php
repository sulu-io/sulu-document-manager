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
     * @var DocumentManagerContext
     */
    private $context;

    /**
     * @var PathSegmentRegistry
     */
    private $pathSegmentRegistry;

    public function __construct(PathSegmentRegistry $pathSegmentRegistry)
    {
        $this->pathSegmentRegistry = $pathSegmentRegistry;
    }

    public function getInspector(DocumentManagerContext $context)
    {
        return new DocumentInspector(
            $context->getRegistry(),
            $this->pathSegmentRegistry,
            $context->getProxyFactory()
        );
    }
}
