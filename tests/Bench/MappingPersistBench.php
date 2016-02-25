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

/**
 * @Groups({"mapping_persist"})
 * @BeforeMethods({"init"})
 * @Iterations(4)
 * @Warmup(10)
 * @Revs(10)
 * @OutputTimeUnit("milliseconds")
 */
class MappingPersistBench extends BaseBench
{
    public function init()
    {
        $this->initPhpcr();
    }

    public function benchPersistMapping5()
    {
        static $rev = 0;
        $document = $this->getDocumentManager()->create('mapping_5');
        $document->one = $rev;
        $document->two = $rev;
        $document->three = $rev;
        $document->four = $rev;
        $document->five = $rev;

        $this->getDocumentManager()->persist($document, 'de', [
            'path' => '/test/to/node-' . $rev,
            'auto_create' => true,
        ]);
        ++$rev;
    }

    public function benchPersistMapping10()
    {
        static $rev = 0;
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

        $this->getDocumentManager()->persist($document, 'de', [
            'path' => '/test/to/node-' . $rev,
            'auto_create' => true,
        ]);
        ++$rev;
    }
}
