<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Functional\DocumentManager;

use Sulu\Component\DocumentManager\Tests\Functional\BaseTestCase;

class DocumentManagerTest extends BaseTestCase
{
    public function setUp()
    {
        $this->initPhpcr();
    }

    private function createDocument($title)
    {
        $manager = $this->getDocumentManager();
        $document = $manager->create('full');

        $manager->persist($document, 'en', array(
            'path' => self::BASE_PATH . '/' . $title,
            'auto_create' => true,
        ));
        $manager->flush();

        return $document;
    }

    /**
     * Test select from alias.
     */
    public function testFromAlias()
    {
        $this->createDocument('Hello');
        $manager = $this->getDocumentManager();
        $builder = $manager->createQueryBuilder();
        $query = $builder->from()->document('full', 'p')->end()->getQuery();
        $results = $query->execute();
        $this->assertCount(1, $results);
    }

    /**
     * Test select from document class.
     */
    public function testFromDocumentClass()
    {
        $this->createDocument('bar');
        $manager = $this->getDocumentManager();
        $builder = $manager->createQueryBuilder();
        $query = $builder->from()->document('Sulu\Component\DocumentManager\Tests\Functional\Model\FullDocument', 'p')->end()->getQuery();
        $results = $query->execute();
        $this->assertCount(1, $results);
    }
}
