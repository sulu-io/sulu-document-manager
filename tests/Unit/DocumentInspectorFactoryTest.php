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

use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentInspectorFactory;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use Sulu\Component\DocumentManager\ProxyFactory;

class DocumentInspectorFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentInspectorFactory
     */
    private $factory;

    /**
     * @var PathSegmentRegistry
     */
    private $pathSegmentRegistry;

    /**
     * @var DocumentRegistry
     */
    private $registry;

    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    public function setUp()
    {
        $this->pathSegmentRegistry = $this->prophesize(PathSegmentRegistry::class);
        $this->registry = $this->prophesize(DocumentRegistry::class);
        $this->proxyFactory = $this->prophesize(ProxyFactory::class);
        $this->manager = $this->prophesize(DocumentManagerInterface::class);
        $this->manager->getRegistry()->willReturn($this->registry->reveal());
        $this->manager->getProxyFactory()->willReturn($this->proxyFactory->reveal());

        $this->factory = new DocumentInspectorFactory($this->pathSegmentRegistry->reveal());
    }

    /**
     * It should return the inspector.
     */
    public function testGetInspector()
    {
        $inspector = $this->factory->getInspector($this->manager->reveal());
        $this->assertInstanceOf(DocumentInspector::class, $inspector);
    }
}
