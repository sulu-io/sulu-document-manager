<?php

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
     * It should get a field mapping
     * It should say if it has a field mapping.
     */
    public function testGetHasFieldMapping()
    {
        $mapping = array('class' => 'stdClass');
        $this->metadata->addFieldMapping('foobar', $mapping);
        $this->assertTrue($this->metadata->hasFieldMapping('foobar'));
        $this->assertFalse($this->metadata->hasFieldMapping('foobar_not'));
        $mapping = $this->metadata->getFieldMapping('foobar');
        $this->assertEquals('stdClass', $mapping['class']);
    }

    /**
     * It should throw an exception if an unknown field mapping is requested.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage not known
     */
    public function testUnknownFieldMapping()
    {
        $this->metadata->getFieldMapping('foobar');
    }
}
