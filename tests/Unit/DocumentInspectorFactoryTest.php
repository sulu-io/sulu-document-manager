<?php

namespace Sulu\Component\DocumentManager\Tests\Unit;

use Sulu\Component\DocumentManager\DocumentInspectorFactory;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use Sulu\Component\DocumentManager\ProxyFactory;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManagerContext;

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
        $this->context = $this->prophesize(DocumentManagerContext::class);
        $this->context->getRegistry()->willReturn($this->registry->reveal());
        $this->context->getProxyFactory()->willReturn($this->proxyFactory->reveal());

        $this->factory = new DocumentInspectorFactory($this->pathSegmentRegistry->reveal());
        $this->factory->setContext($this->context->reveal());
    }

    /**
     * It should return the inspector.
     */
    public function testGetInspector()
    {
        $inspector = $this->factory->getInspector();
        $this->assertInstanceOf(DocumentInspector::class, $inspector);
    }
}
