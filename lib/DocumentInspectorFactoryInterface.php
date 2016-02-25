<?php

namespace Sulu\Component\DocumentManager;

use Sulu\Component\DocumentManager\InspectorFactoryInterface;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use Sulu\Component\DocumentManager\DocumentManagerContext;

/**
 * Document inspector factory.
 */
interface DocumentInspectorFactoryInterface
{
    public function getInspector(DocumentManagerContext $context);
}
