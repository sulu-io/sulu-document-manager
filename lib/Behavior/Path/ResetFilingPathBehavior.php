<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Behavior\Path;

use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;

/**
 * Resets the path to base path.
 *
 * this is used for example with the AliasFilingBehavior
 */
interface ResetFilingPathBehavior extends ParentBehavior
{
}
