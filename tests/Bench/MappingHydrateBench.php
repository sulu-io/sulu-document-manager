<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Bench;

use PHPCR\ImportUUIDBehaviorInterface;

/**
 * @Groups({"mapping_hydrate"})
 * @Iterations(4)
 * @Revs(10)
 * @BeforeMethods({"setUp"})
 */
class MappingHydrateBench extends BaseBench
{
    public function setUp()
    {
        $this->initPhpcr();
        $this->getSession()->importXML(self::BASE_PATH, __DIR__ . '/test.xml', ImportUUIDBehaviorInterface::IMPORT_UUID_COLLISION_REMOVE_EXISTING);
        $this->getSession()->save();
    }

    public function benchHydrateMapping10()
    {
        for ($index = 0; $index < 10; ++$index) {
            $this->getDocumentManager()->find('/test/jcr:root/test/to/node-' . $index);
        }
    }

    public function benchHydrateMapping5()
    {
        for ($index = 0; $index < 5; ++$index) {
            $this->getDocumentManager()->find('/test/jcr:root/test/to/node-' . $index);
        }
    }
}
