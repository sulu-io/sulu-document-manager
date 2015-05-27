<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Functional;

use Sulu\Component\DocumentManager\Tests\Bootstrap;

abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    const BASE_NAME = 'test';
    const BASE_PATH = '/test';

    private $container;

    protected function initPhpcr()
    {
        $nodeManager = $this->getContainer()->get('sulu_document_manager.node_manager');
        $nodeManager->purgeWorkspace();
        $nodeManager->save();
    }

    protected function loadDump($name)
    {
        $this->getSession()->importXML(self::BASE_PATH, __DIR__ . '/../Dumps/' . $name, ImportUUIDBehaviorInterface::IMPORT_UUID_COLLISION_REMOVE_EXISTING);
        $this->getSession()->save();
    }

    protected function getSession()
    {
        return $this->getContainer()->get('doctrine_phpcr.default_session');
    }

    protected function getContainer()
    {
        if ($this->container) {
            return $this->container;
        }

        $this->container = Bootstrap::createContainer();

        return $this->container;
    }

    protected function getDocumentManager()
    {
        return $this->getContainer()->get('sulu_document_manager.document_manager');
    }

    protected function generateDataSet(array $options)
    {
        $options = array_merge(array(
            'locales' => array('en'),
        ), $options);

        $manager = $this->getDocumentManager();
        $document = $manager->create('full');

        foreach ($options['locales'] as $locale) {
            $manager->persist($document, $locale, array(
                'path' => self::BASE_PATH,
            ));
        }
    }
}
