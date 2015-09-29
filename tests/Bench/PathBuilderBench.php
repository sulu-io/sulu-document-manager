<?php

namespace Sulu\Component\DocumentManager\tests\Bench;

use PhpBench\Benchmark;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\DocumentManager\PathSegmentRegistry;

/**
 * @group path_builder
 */
class PathBuilderBench implements Benchmark
{
    private $pathBuilder;

    public function setUp()
    {
        $registry = new PathSegmentRegistry([
            'one' => 'hello',
            'two' => 'goodbye',
        ]);
        $this->pathBuilder = new PathBuilder($registry);
    }

    /**
     * @description Build path 1000 times
     * @revs 1000
     * @beforeMethod setUp
     * @paramProvider provideElements
     */
    public function benchBuild($iteration)
    {
        $this->pathBuilder->build($iteration->getParameter('elements'));
    }

    public function provideElements()
    {
        return [
            [
                'elements' => ['one', 'two', 'three'],
            ],
            [
                'elements' => ['%one', '%two%', 'three'],
            ],
        ];
    }
}
