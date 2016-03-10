<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit;

use Sulu\Component\DocumentManager\Metadata;

class MetadataTest extends \PHPUnit_Framework_TestCase
{
    private $metadata;

    public function setUp()
    {
        $this->metadata = new Metadata();
    }

    /**
     * It should throw an exception if no class is set and the ReflectionClass
     * is requested.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testNoClassGetReflection()
    {
        $this->metadata->getReflectionClass();
    }

    /**
     * It should return the reflection class.
     */
    public function testReflectionClass()
    {
        $this->metadata->setClass('\stdClass');
        $reflection = $this->metadata->getReflectionClass();

        $this->assertInstanceOf('ReflectionClass', $reflection);
        $this->assertEquals('stdClass', $reflection->name);

        $this->assertSame($reflection, $this->metadata->getReflectionClass());

        $this->metadata->setClass('\stdClass');
        $this->assertNotSame($reflection, $this->metadata->getReflectionClass());
    }

    /**
     * It should set properties on documents.
     * It should get properties on documents.
     */
    public function testGetSetFieldValue()
    {
        $object = new TestClass();
        $this->metadata->setClass(TestClass::class);
        $this->metadata->addFieldMapping('foo', []);

        $this->assertEquals('bar', $this->metadata->getFieldValue($object, 'foo'));

        $this->metadata->setFieldValue($object, 'foo', 'foo');
        $this->assertEquals('foo', $this->metadata->getFieldValue($object, 'foo'));
    }

    /**
     * It should throw an exception if trying to get a field that has not been
     * mapped.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Field "asdoo" is not mapped for document "Sulu\Component\DocumentManager\Tests\Unit\TestClass". Mapped fields: "foo"
     */
    public function testGetFieldValueInvalid()
    {
        $object = new TestClass();
        $this->metadata->setClass(TestClass::class);
        $this->metadata->addFieldMapping('foo', []);

        $this->metadata->getFieldValue($object, 'asdoo');
    }
}

class TestClass
{
    private $foo = 'bar';
}
