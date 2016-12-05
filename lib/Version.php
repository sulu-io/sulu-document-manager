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
 * Represents the version information on a document.
 */
class Version
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $locale;

    /**
     * @param string $id
     * @param string $locale
     */
    public function __construct($id, $locale)
    {
        $this->id = $id;
        $this->locale = $locale;
    }
}
