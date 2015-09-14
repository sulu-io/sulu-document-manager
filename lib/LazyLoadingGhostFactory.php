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

use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingGhostFactory as BaseLazyLoadingGhostFactory;

/**
 * Extends the LazyLoadingGhostFactory of the ProxyManager, to make it configureable according to the needs of Sulu.
 */
class LazyLoadingGhostFactory extends BaseLazyLoadingGhostFactory
{
    public function __construct($cacheDirectory)
    {
        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0777, true);
        }

        $configuration = new Configuration();
        $configuration->setProxiesTargetDir($cacheDirectory);

        parent::__construct($configuration);
    }
}
