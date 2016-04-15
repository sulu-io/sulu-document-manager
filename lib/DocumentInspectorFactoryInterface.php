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

use Sulu\Component\DocumentManager\DocumentManagerContext;

/**
 * Document inspector factory.
 *
 * Allows the client to implement their own DocumentInspector implementations.
 *
 * As Document inspectors are dependent upon a document context, and as the
 * number of document contexts is unbounded it would be impractical to manually
 * set the document context on each individual instance.
 *
 * The factory should create the DocumentInspector instance using the given
 * document context.
 */
interface DocumentInspectorFactoryInterface
{
    /**
     * Create a new DocumentInspector instance using the given document
     * context.
     *
     * @param DocumentManagerContext
     *
     * @return DocumentInspector
     */
    public function getInspector(DocumentManagerContext $context);
}
