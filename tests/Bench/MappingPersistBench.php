<?php

namespace Sulu\Component\DocumentManager\Tests\Bench;

/**
 * @processIsolation iteration
 * @group mapping_persist
 */
class MappingPersistBench extends BaseBench
{
    public function init()
    {
        $this->initPhpcr();
    }

    /**
     * @description Persist document with 5 mapping fileds (no flush)
     * @beforeMethod init
     * @revs 10
     * @iterations 2
     */
    public function benchPersistMapping5($iter, $rev)
    {
        $document = $this->getDocumentManager()->create('mapping_5');
        $document->one = $rev;
        $document->two = $rev;
        $document->three = $rev;
        $document->four = $rev;
        $document->five = $rev;

        $this->getDocumentManager()->persist($document, 'de', array(
            'path' => '/test/to/node-' . $rev,
            'auto_create' => true,
        ));
    }

    /**
     * @description Persist document with 10 mapping fileds (no flush)
     * @beforeMethod init
     * @revs 10
     * @iterations 2
     */
    public function benchPersistMapping10($iter, $rev)
    {
        $document = $this->getDocumentManager()->create('mapping_10');
        $document->one = $rev;
        $document->two = $rev;
        $document->three = $rev;
        $document->four = $rev;
        $document->five = $rev;
        $document->six = $rev;
        $document->seven = $rev;
        $document->eight = $rev;
        $document->nine = $rev;
        $document->ten = $rev;

        $this->getDocumentManager()->persist($document, 'de', array(
            'path' => '/test/to/node-' . $rev,
            'auto_create' => true,
        ));
    }
}
