<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Exception;

use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

class DocumentManagerExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * It should prefix the document manager name.
     * It should return the name of the document manager.
     */
    public function testPrefixDocumentManagerName()
    {
        $exception = new DocumentManagerException('Hello', 'foo');
        $this->assertEquals('[foo] Hello', $exception->getMessage());
        $this->assertEquals('foo', $exception->getDocumentManagerName());
    }

    /**
     * It should allow the document manager name to be set via. a setter.
     */
    public function testSetDocumentManagername()
    {
        $exception = new DocumentManagerException('Hello');
        $exception->setDocumentManagerName('foo');
        $this->assertEquals('[foo] Hello', $exception->getMessage());
    }

    /**
     * It should not prefix a name if no name is given.
     */
    public function testNoDocumentManagerName()
    {
        $exception = new DocumentManagerException('Hello');
        $this->assertEquals('Hello', $exception->getMessage());
    }

    /**
     * It should throw an exception if the document manager name is set twice.
     *
     * @expectedException \Sulu\Component\DocumentManager\Exception\RuntimeException
     */
    public function testSetNameTwice()
    {
        $exception = new DocumentManagerException('Hello', 'foo');
        $exception->setDocumentManagerName('foo');
    }
}
