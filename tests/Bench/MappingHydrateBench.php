<?php

namespace Sulu\Component\DocumentManager\Tests\Bench;

use PHPCR\ImportUUIDBehaviorInterface;

/**
 * @processIsolation iteration
 * @group mapping_hydrate
 */
class MappingHydrateBench extends BaseBench
{
    public function setUp()
    {
        $this->initPhpcr();
        $this->getSession()->importXML(self::BASE_PATH, __DIR__ . '/test.xml', ImportUUIDBehaviorInterface::IMPORT_UUID_COLLISION_REMOVE_EXISTING);
        $this->getSession()->save();
    }

    /**
     * @description Hydrate 10 documents
     * @iterations 4
     * @revs 10
     */
    public function benchHydrateMapping10($iter, $rev)
    {
        for ($index = 0; $index < 10; $index++) {
            $this->getDocumentManager()->find('/test/jcr:root/test/to/node-' .  $index);
        }
    }

    /**
     * @description Hydrate 20 documents
     * @iterations 4
     * @revs 10
     */
    public function benchHydrateMapping5($iter, $rev)
    {
        for ($index = 0; $index < 20; $index++) {
            $this->getDocumentManager()->find('/test/jcr:root/test/to/node-' .  $index);
        }
    }
}
