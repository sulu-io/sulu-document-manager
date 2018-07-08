<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit\Slugifier;

use Sulu\Component\DocumentManager\Slugifier\NodeNameSlugifier;
use Sulu\Component\DocumentManager\Tests\Bootstrap;
use Symfony\Cmf\Api\Slugifier\SlugifierInterface;
use Symfony\Component\DependencyInjection\Container;

class NodeNameSlugifierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var SlugifierInterface
     */
    private $slugifier;

    /**
     * @var NodeNameSlugifier
     */
    private $nodeNameSlugifier;

    protected function setUp()
    {
        $this->container = Bootstrap::createContainer();
        $this->slugifier = $this->container->get('sulu_document_manager.slugifier');
        $this->nodeNameSlugifier = new NodeNameSlugifier($this->slugifier);
    }

    public function testSlugify()
    {
        $this->assertEquals('test-article', $this->nodeNameSlugifier->slugify('Test article'));
    }

    public function testSlugifyLatinExtended()
    {
        $this->assertEquals('rozszerzony-lacinska', $this->nodeNameSlugifier->slugify('Rozszerzony łacińska'));
    }

    public function testSlugifyNonLatin()
    {
        // ukrainian cyrillic
        $this->assertEquals('testova-stattia-z-i-yi-ie-g', $this->nodeNameSlugifier->slugify('Тестова стаття з і, ї, є, ґ'));
        // japanese
        $this->assertEquals('tesutoji-shi', $this->nodeNameSlugifier->slugify('テスト記事'));
        // chinese (simplified)
        $this->assertEquals('ce-shi-wen-zhang', $this->nodeNameSlugifier->slugify('测试文章'));
        // russian cyrillic
        $this->assertEquals('testovaia-statia-s-io-y', $this->nodeNameSlugifier->slugify('Тестовая статья с ё, ъ, ы'));
    }

    public function provide10eData()
    {
        return [
            ['10e', '10-e'],
            ['.10e', '10-e'],
            ['-10e', '10-e'],
            ['%10e', '10-e'],
            ['test-10e-name', 'test-10-e-name'],
            ['test.10e-name', 'test-10-e-name'],
            ['test%10e-name', 'test-10-e-name'],
            ['test-10E-name', 'test-10-e-name'],
            ['test.10E-name', 'test-10-e-name'],
            ['test%10E-name', 'test-10-e-name'],
            ['test10E-name', 'test10-e-name'],
            ['test-9e-name', 'test-9-e-name'],
            ['test-500e-name', 'test-500-e-name'],
            ['тестова-500e-назва', 'testova-500-e-nazva'],
        ];
    }

    /**
     * @dataProvider provide10eData
     */
    public function testSlugify10e($actual, $expected)
    {
        $this->assertEquals($expected, $this->nodeNameSlugifier->slugify($actual));
    }
}
