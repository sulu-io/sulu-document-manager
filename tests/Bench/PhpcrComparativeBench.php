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
 * @Groups({"phpcr_comparison"})
 * @Iterations(4)
 * @ParamProviders({"provideNodeTotals"})
 * @BeforeMethods({"init"})
 */
class PhpcrComparativeBench extends BaseBench
{
    public function init()
    {
        $this->initPhpcr();
    }

    public function benchCreatePersist($params)
    {
        $manager = $this->getManager();

        for ($i = 0; $i < $params['nb_nodes']; ++$i) {
            $document = $manager->create('full');
            foreach (['en', 'de', 'fr'] as $locale) {
                $manager->persist($document, $locale, [
                    'path' => self::BASE_PATH . '/node-' . $i,
                ]);
            }
        }

        $manager->flush();
    }

    public function benchCreatePersistPhpcr($params)
    {
        $session = $this->getSession();
        $baseNode = $session->getNode(self::BASE_PATH);

        for ($i = 0; $i < $params['nb_nodes']; ++$i) {
            $node = $baseNode->addNode('node-' . $i);
            foreach (['en', 'de', 'fr'] as $locale) {
                $node->addMixin('mix:test');
                $node->setProperty('lsys:' . $locale . '-created', new \DateTime());
                $node->setProperty('lsys:' . $locale . '-changed', new \DateTime());
            }
        }

        $session->save();
    }

    public function provideNodeTotals()
    {
        return [
            [
                'nb_nodes' => 1,
            ],
            [
                'nb_nodes' => 10,
            ],
            [
                'nb_nodes' => 100,
            ],
        ];
    }
}
