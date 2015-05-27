<?php

namespace Sulu\Component\DocumentManager\Tests\Bench;

/**
 * @processIsolation iteration
 * @group mapping_hydrate
 */
class MappingHydrateBench extends BaseBench
{
    public function setUp()
    {
        $this->initPhpcr();
        $this->loadDump('20-nodes.xml');
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
