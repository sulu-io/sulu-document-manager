<?php

namespace Sulu\Component\DocumentManager\Tests\Unit\Event;

use Sulu\Component\DocumentManager\Event\AbstractEvent;
use Sulu\Component\DocumentManager\DocumentManagerContext;

class AbstractEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractEvent
     */
    private $event;

    /**
     * @var DocumentManagerContext
     */
    private $context;

    public function setUp()
    {
        $this->event = $this->getMockForAbstractClass(AbstractEvent::class);
        $this->context = $this->prophesize(DocumentManagerContext::class);
    }

    /**
     * It be able to have a context attached.
     * It should be able to retrieve the context.
     */
    public function testAttachGetContext()
    {
        $this->event->attachContext($this->context->reveal());
        $this->assertSame(
            $this->context->reveal(),
            $this->event->getContext()
        );
    }

    /**
     * It should throw an exception if no context has been set.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No context has been attached
     */
    public function testGetContextNoneSet()
    {
        $this->event->getContext();
    }
}
