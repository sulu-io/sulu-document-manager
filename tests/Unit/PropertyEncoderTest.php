<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit;

use Prophecy\Argument;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\DocumentManager\PropertyEncoder;

class PropertyEncoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var NamespaceRegistry
     */
    private $namespaceRegistry;

    public function setUp()
    {
        $map = [
            'system' => 'nsys',
            'system_localized' => 'lsys',
        ];

        $this->namespaceRegistry = $this->prophesize(NamespaceRegistry::class);
        $this->namespaceRegistry->getPrefix(Argument::type('string'))->will(function ($args) use ($map) {
            return $map[$args[0]];
        });
        $this->encoder = new PropertyEncoder($this->namespaceRegistry->reveal());
    }

    /**
     * It should encode localized system properties.
     */
    public function testEncodeLocalizedSystem()
    {
        $name = $this->encoder->localizedSystemName('created', 'fr');
        $this->assertEquals('lsys:fr-created', $name);
    }

    /**
     * It should encode system properties.
     */
    public function testEncodeSystem()
    {
        $name = $this->encoder->systemName('created');
        $this->assertEquals('nsys:created', $name);
    }

    /**
     * It should throw an exception if the locale is null.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Locale is mandatory
     * @dataProvider provideEncodeLocalizedException
     */
    public function testEncodeLocalizedException($name)
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $this->encoder->encode($name, 'test', null);
    }

    public function provideEncodeLocalizedException()
    {
        return [
            ['system_localized'],
            ['content_localized'],
        ];
    }
}
