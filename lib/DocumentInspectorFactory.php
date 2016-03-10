<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

/**
 * Document inspector factory for the default DocumentInspector.
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

    /**
     * {@inheritdoc}
     */
    public function getInspector(DocumentManagerInterface $manager)
    {
        if ($this->inspector) {
            return $this->inspector;
        }

        $this->inspector = new DocumentInspector(
            $manager->getRegistry(),
            $this->pathSegmentRegistry,
            $manager->getProxyFactory()
        );

        return $this->inspector;
    }
}
