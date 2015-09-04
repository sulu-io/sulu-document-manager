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
     * It should return a specific field mapping.
     */
    public function testGetFieldMapping()
    {
        $this->metadata->addFieldMapping('hello', ['property' => 'foo']);
        $this->assertContains([
                'property' => 'foo',
            ],
            $this->metadata->getFieldMapping('hello')
        );
    }
}
