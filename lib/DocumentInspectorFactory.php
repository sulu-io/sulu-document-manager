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

    public function attachContext(DocumentManagerContext $context)
    {
        $this->context = $context;
    }

    public function getInspector()
    {
        return new DocumentInspector(
            $this->context->getRegistry(),
            $this->pathSegmentRegistry,
            $this->context->getProxyFactory()
        );
    }
}
