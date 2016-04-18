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
        $this->manager1 = $this->prophesize(DocumentManagerInterface::class);
        $this->manager1->getRegistry()->willReturn($this->registry->reveal());
        $this->manager1->getProxyFactory()->willReturn($this->proxyFactory->reveal());
        $this->manager2 = $this->prophesize(DocumentManagerInterface::class);
        $this->manager2->getRegistry()->willReturn($this->registry->reveal());
        $this->manager2->getProxyFactory()->willReturn($this->proxyFactory->reveal());

        $this->factory = new DocumentInspectorFactory($this->pathSegmentRegistry->reveal());
    }

    /**
     * It should return the inspector.
     */
    public function testGetInspector()
    {
        $inspector = $this->factory->getInspector($this->manager1->reveal());
        $this->assertInstanceOf(DocumentInspector::class, $inspector);
    }

    /**
     * It should return different instances for each manager instance.
     */
    public function testGetInspectorDifferentInstances()
    {
        $inspector1 = $this->factory->getInspector($this->manager1->reveal());
        $inspector2 = $this->factory->getInspector($this->manager2->reveal());

        $this->assertNotSame($inspector1, $inspector2);
    }

    /**
     * Multiple calls should return the same inspector.
     */
    public function testGetInspectorMultipleCalls()
    {
        $inspector1 = $this->factory->getInspector($this->manager1->reveal());
        $inspector2 = $this->factory->getInspector($this->manager1->reveal());

        $this->assertSame($inspector1, $inspector2);
    }
}
