<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Bench;

use PhpBench\BenchCase;
use PhpBench\BenchIteration;

class BenchmarkBench extends BaseBench
{
    public function init()
    {
        $this->initPhpcr();
    }

    /**
     * @description persist and find nodes using the Document Manager
     * @iterations 4
     * @paramProvider provideNodeTotals
     * @paramProvider provideLocales
     * @beforeMethod init
     */
    public function benchCreatePersist(BenchIteration $iteration)
    {
        $manager = $this->getDocumentManager();
        $locales = $iteration->getParameter('locales');

        for ($i = 0; $i < $iteration->getParameter('nb_nodes'); $i++) {
            $document = $manager->create('full');
            foreach ($locales as $locale) {
                $manager->persist($document, $locale, array(
                    'path' => self::BASE_PATH . '/node-' . $i
                ));
            }
        }

        $manager->flush();
    }

    /**
     * @description persist and find nodes using raw PHPCR
     * @iterations 4
     * @paramProvider provideNodeTotals
     * @paramProvider provideLocales
     * @beforeMethod init
     */
    public function benchCreatePersistPhpcr(BenchIteration $iteration)
    {
        $session = $this->getSession();
        $baseNode = $session->getNode(self::BASE_PATH);

        for ($i = 0; $i < $iteration->getParameter('nb_nodes'); $i++) {
            $node = $baseNode->addNode('node-' . $i);
            foreach ($iteration->getParameter('locales') as $locale) {
                $node->addMixin('mix:test');
                $node->setProperty('lsys:' . $locale .'-created', new \DateTime());
                $node->setProperty('lsys:' . $locale .'-changed', new \DateTime());
            }
        }

        $session->save();
    }

    public function provideNodeTotals()
    {
        return array(
            array(
                'nb_nodes' => 1,
            ),
            array(
                'nb_nodes' => 10, 
            ),
            array(
                'nb_nodes' => 100, 
            ),
        );
    }

    public function provideLocales()
    {
        return array(
            array(
                'locales' => array('en'),
            ),
            array(
                'locales' => array('en', 'de'),
            ),
            array(
                'locales' => array('en', 'de', 'fr'),
            ),
        );
    }
}
