<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Functional\DocumentManager;

use Sulu\Component\DocumentManager\Tests\Functional\BaseTestCase;
use Sulu\Component\DocumentManager\Tests\Functional\Model\FullDocument;
use Sulu\Component\DocumentManager\Tests\Functional\Model\IssueDocument;

/**
 * Query builder tests.
 *
 * Note that we currently extend the PHPCR-ODM query builder, so most of the query
 * builder is tested in that package.
 *
 * This class just needs to test the Sulu Converter which converts the query builder object
 * into a PHPCR query. Testing this as a unit would be very complicated due to having to mock
 * the PHPCR QOMF.
 */
class QueryBuilderTest extends BaseTestCase
{
    public function setUp()
    {
        $this->initPhpcr();
        $manager = $this->getDocumentManager();

        $document1 = $manager->create('full');
        $document1->setTitle('Hello');
        $document1->setBody('Hello this is something');
        $document1->setStatus('open');
        $manager->persist($document1, 'en', ['path' => '/test/document1', 'auto_create' => true]);

        $document2 = $manager->create('full');
        $document2->setTitle('Goodbye');
        $document2->setBody('Goodbye and Adios');
        $document2->setStatus('closed');
        $document2->setReference($document1);
        $manager->persist($document2, 'en', ['path' => '/test/document2', 'auto_create' => true]);

        $document2->setTitle('Aufweidersehn');
        $document2->setBody('Gutetag, das ist etwas');
        $manager->persist($document2, 'de', ['path' => '/test/document2']);

        $issue1 = $manager->create('issue');
        $issue1->setName('Does it work?');
        $issue1->setStatus('open');
        $manager->persist($issue1, null, [
            'path' => '/test/issue1',
            'auto_create' => true,
        ]);

        $issue2 = $manager->create('issue');
        $issue2->setName('What shall we do today?');
        $issue2->setStatus('closed');

        $manager->persist($issue2, null, [
            'path' => '/test/issue2',
            'auto_create' => true,
        ]);

        $issue3 = $manager->create('issue');
        $issue3->setName('The hot water at ten and a closed car at four');
        $issue3->setStatus('open');

        $manager->persist($issue3, null, [
            'path' => '/test/issue3',
            'auto_create' => true,
        ]);

        $manager->flush();
    }

    /**
     * It should query from an alias source.
     */
    public function testFromAlias()
    {
        $manager = $this->getDocumentManager();
        $builder = $manager->createQueryBuilder();
        $builder->setLocale('en');
        $query = $builder->from()->document('full', 'p')->end()->getQuery();
        $results = $query->execute();
        $this->assertGreaterThan(1, count($results));
    }

    /**
     * It should query from a class FQN source.
     */
    public function testFromDocumentClass()
    {
        $manager = $this->getDocumentManager();
        $builder = $manager->createQueryBuilder();
        $builder->setLocale('en');
        $query = $builder->from()->document('Sulu\Component\DocumentManager\Tests\Functional\Model\FullDocument', 'p')->end()->getQuery();
        $results = $query->execute();
        $this->assertGreaterThan(1, count($results));
    }

    /**
     * Test query by field.
     */
    public function testQueryByField()
    {
        $builder = $this->getDocumentManager()->createQueryBuilder();
        $builder
            ->setLocale('en')
            ->from()
                ->document('full', 'f')
            ->end()
            ->where()
                ->eq()->field('f.title')->literal('Hello');

        $results = $builder->getQuery()->execute();
        $this->assertEquals(1, count($results));

        return $builder;
    }

    /**
     * It should take the locale into account when querying.
     *
     * @depends testQueryByField
     */
    public function testQueryByFieldWithLocale($builder)
    {
        $builder->setLocale('az');

        $results = $builder->getQuery()->execute();
        $this->assertEquals(0, count($results));
    }

    /**
     * It should query on fields mapped by subscribers.
     */
    public function testQueryBySubscriberFields()
    {
        $oneHourAgo = new \DateTime();
        $oneHourAgo->modify('-1 hour');
        $builder = $this->getDocumentManager()->createQueryBuilder();
        $builder
            ->setLocale('en')
            ->from()
                ->document('full', 'f')
            ->end()
            ->where()
                ->fieldIsset('f.created');

        $results = $builder->getQuery()->execute();
        $this->assertEquals(2, count($results));
    }

    /**
     * It should join sources, hydrating objects from right().
     */
    public function testQueryBuilderFromJoinHydrateRight()
    {
        $builder = $this->getDocumentManager()->createQueryBuilder();
        $builder->setLocale('en');
        $builder->from('i')->joinInner()
            ->left()->document('full', 'f')->end()
            ->right()->document('issue', 'i')->end()
            ->condition()->equi('f.status', 'i.status');
        $builder->where()->eq()->field('i.status')->literal('closed');
        $results = $builder->getQuery()->execute();

        $this->assertEquals(1, count($results));
        $this->assertContainsOnlyInstancesOf(IssueDocument::class, $results->toArray());
    }

    /**
     * It should join sources hydrating objects from left().
     */
    public function testQueryBuilderFromJoinHydrateLeft()
    {
        $builder = $this->getDocumentManager()->createQueryBuilder();
        $builder->setLocale('en');
        $builder->from('f')->joinInner()
            ->left()->document('full', 'f')->end()
            ->right()->document('issue', 'i')->end()
            ->condition()->equi('f.status', 'i.status');
        $builder->where()->eq()->field('i.status')->literal('closed');
        $results = $builder->getQuery()->execute();

        $this->assertEquals(1, count($results));
        $this->assertContainsOnlyInstancesOf(FullDocument::class, $results->toArray());
    }

    /**
     * It should throw an exception if the primary alias is not given when selecting from a join.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testQueryBuilderFromJoinNoPrimary()
    {
        $builder = $this->getDocumentManager()->createQueryBuilder();
        $builder->setLocale('en');
        $builder->from()->joinInner()
            ->left()->document('full', 'f')->end()
            ->right()->document('issue', 'i')->end()
            ->condition()->equi('f.status', 'i.status');
        $builder->getQuery();
    }

    /**
     * It should be able to change locales.
     */
    public function testQueryBuilderDifferentLocales()
    {
        $builder = $this->getDocumentManager()->createQueryBuilder();
        $builder->setLocale('en');
        $builder->from()->document('full', 'f');
        $builder->where()->eq()->field('f.status')->literal('closed');

        $results = $builder->getQuery()->execute();
        $this->assertEquals('Goodbye', $results->current()->getTitle());

        $builder->setLocale('de');
        $results = $builder->getQuery()->execute();
        $this->assertEquals('Aufweidersehn', $results->current()->getTitle());
    }

    /**
     * It should limit the results.
     */
    public function testQueryBuilderLimit()
    {
        $builder = $this->getDocumentManager()->createQueryBuilder();
        $builder->setLocale('en');
        $builder->from()->document('issue', 'i');
        $results = $builder->getQuery()->execute();
        $this->assertCount(3, $results->toArray());

        $builder->setMaxResults(2);
        $results = $builder->getQuery()->execute();
        $this->assertCount(2, $results->toArray());

        $builder->setMaxResults(1);
        $results = $builder->getQuery()->execute();
        $this->assertCount(1, $results->toArray());

        $builder->setFirstResult(1);
        $results = $builder->getQuery()->execute();
        $this->assertCount(1, $results->toArray());
    }

    /**
     * It should throw an exception if you try and select from multiple sources without
     * specifying a primary selector.
     *
     * @expectedException \InvalidArgumentException 
     * @expectedExceptionMessage You must specify a primary alias when selecting from multiple document sources
     */
    public function testMultipleSelectorsNoPrimary()
    {
        $builder = $this->getDocumentManager()->createQueryBuilder();
        $builder->setLocale('en');
        $builder->from()->joinInner()
            ->left()->document('full', 'f')->end()
            ->right()->document('issue', 'i')->end()
            ->condition()->equi('f.status', 'i.status');
        $builder->where()->eq()->field('i.status')->literal('closed');
        $builder->getQuery();
    }
}
