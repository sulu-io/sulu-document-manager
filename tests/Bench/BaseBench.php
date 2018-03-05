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

use Sulu\Component\DocumentManager\Tests\Bootstrap;

abstract class BaseBench
{
    const BASE_NAME = 'test';

    const BASE_PATH = '/test';

    private $container;

    protected function initPhpcr()
    {
        $session = $this->getSession();

        if ($session->getRootNode()->hasNode(self::BASE_NAME)) {
            $session->removeItem(self::BASE_PATH);
            $session->save();
        }

        $session->getRootNode()->addNode(self::BASE_NAME);
    }

    protected function getContainer()
    {
        if ($this->container) {
            return $this->container;
        }

        $this->container = Bootstrap::createContainer();

        return $this->container;
    }

    protected function getSession()
    {
        $session = $this->getContainer()->get('doctrine_phpcr.session');

        return $session;
    }

    protected function getDocumentManager()
    {
        return $this->getContainer()->get('sulu_document_manager.document_manager');
    }
}
