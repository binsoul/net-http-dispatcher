<?php

namespace BinSoul\Test\Net\Http\Dispatcher;

use BinSoul\Net\Http\Dispatcher\DefaultContext;

class DefaultContextTest extends \PHPUnit_Framework_TestCase
{
    public function test_indicates_parameter_existance()
    {
        $context = new DefaultContext(['name' => 'value']);
        $this->assertTrue($context->hasParameter('name'));
        $this->assertFalse($context->hasParameter('foo'));
    }

    public function test_returns_parameter()
    {
        $context = new DefaultContext(['name' => 'value']);
        $this->assertEquals('value', $context->getParameter('name'));
    }

    public function test_returns_default()
    {
        $context = new DefaultContext(['name' => 'value']);
        $this->assertEquals('bar', $context->getParameter('foo', 'bar'));
    }

    public function test_returns_new_instance_with_parameter()
    {
        $context = new DefaultContext(['name' => 'value']);
        $newContext = $context->withParameter('foo', 'bar');

        $this->assertNotSame($newContext, $context);

        $this->assertFalse($context->hasParameter('foo'));
        $this->assertEquals('bar', $newContext->getParameter('foo'));
    }
}
