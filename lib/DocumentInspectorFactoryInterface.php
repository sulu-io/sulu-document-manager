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
 * Document inspector factory.
 *
 * Allows the client to implement their own DocumentInspector implementations.
 *
 * As Document inspectors are dependent upon a document manager, and as the
 * number of document managers is unbounded it would be impractical to manually
 * set the document manager on each individual instance.
 *
 * The factory should create the DocumentInspector instance using the given
 * document manager.
 */
interface DocumentInspectorFactoryInterface
{
    /**
     * Create a new DocumentInspector instance using the given document
     * manager.
     *
     * @param DocumentManagerInterface
     *
     * @return DocumentInspector
     */
    public function getInspector(DocumentManagerInterface $manager);
}
