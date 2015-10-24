<?php

namespace BinSoul\Test\Net\Http\Dispatcher;

use BinSoul\Net\Http\Dispatcher\Context;
use BinSoul\Net\Http\Dispatcher\DefaultDispatcher;
use BinSoul\Net\Http\Dispatcher\InvokableFactory;
use BinSoul\Net\Http\Dispatcher\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class InvokableResponder
{
    public $wasCalled = false;

    public function __invoke(
        $id,
        RequestInterface $request,
        Context $context,
        $optionalString = 'optional',
        callable $optionalCallable = null,
        Middleware $optionalInterface = null
    ) {
        if ($id === 1) {
            $this->wasCalled = true;
        }
    }
}

class MethodResponder
{
    public $wasCalled = false;

    public function action(
        $id,
        RequestInterface $request,
        Context $context,
        $optionalString = 'optional',
        callable $optionalCallable = null,
        Middleware $optionalInterface = null
    ) {
        if ($id === 1) {
            $this->wasCalled = true;
        }
    }
}

class MiddlewareImplementation implements Middleware
{
    /** @var */
    private $callNext;
    public $wasCalled = false;

    /**
     * @param bool $callNext
     */
    public function __construct($callNext)
    {
        $this->callNext = $callNext;
    }

    public function __invoke(RequestInterface $request, Context $context, callable $next)
    {
        $this->wasCalled = true;
        if ($this->callNext) {
            $next($request, $context);
        }
    }
}

class DefaultDispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function test_calls_closure_responder()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(ServerRequestInterface::class);

        $wasCalled = false;
        $responder = function (
            $id,
            ServerRequestInterface $request,
            Context $context,
            $optionalString = 'optional',
            callable $optionalCallable = null,
            Middleware $optionalInterface = null
        ) use (&$wasCalled) {
            if ($id === 1) {
                $wasCalled = true;
            }
        };

        $dispatcher = new DefaultDispatcher(['closure' => $responder]);
        $dispatcher->handle($request, ['responder' => 'closure', 'id' => 1]);
        $this->assertTrue($wasCalled);
    }

    public function test_calls_invokable_responder()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $responder = new InvokableResponder();
        $dispatcher = new DefaultDispatcher(['invokable class' => $responder]);
        $dispatcher->handle($request, ['responder' => 'invokable class', 'id' => 1]);
        $this->assertTrue($responder->wasCalled);
    }

    public function test_calls_method_responder()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $responder = new MethodResponder();
        $dispatcher = new DefaultDispatcher(['class method' => $responder]);
        $dispatcher->handle($request, ['responder' => 'class method', 'method' => 'action', 'id' => 1]);
        $this->assertTrue($responder->wasCalled);
    }

    public function test_uses_factory_to_build_responder()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $responder = new InvokableResponder();
        $factory = $this->getMock(InvokableFactory::class);
        $factory->expects($this->once())->method('buildResponder')->willReturn($responder);

        $dispatcher = new DefaultDispatcher(
            ['invokable class' => InvokableResponder::class],
            [],
            'responder',
            'action',
            $factory
        );

        $dispatcher->handle($request, ['responder' => 'invokable class', 'id' => 1]);
        $this->assertTrue($responder->wasCalled);
    }

    public function test_uses_middleware_and_calls_responder()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $responder = new InvokableResponder();
        $middleware1 = new MiddlewareImplementation(true);
        $middleware2 = new MiddlewareImplementation(true);

        $dispatcher = new DefaultDispatcher(
            ['invokable class' => $responder],
            [$middleware1, $middleware2]
        );

        $dispatcher->handle($request, [
            'responder' => 'invokable class',
            'id' => 1,
        ]);

        $this->assertTrue($middleware1->wasCalled);
        $this->assertTrue($middleware2->wasCalled);
        $this->assertTrue($responder->wasCalled);
    }

    public function test_middleware_can_return_response()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $responder = new InvokableResponder();
        $middleware1 = new MiddlewareImplementation(true);
        $middleware2 = new MiddlewareImplementation(false);

        $dispatcher = new DefaultDispatcher(
            ['invokable class' => $responder],
            [
                $middleware1,
                $middleware2,
            ]
        );

        $dispatcher->handle($request, [
            'responder' => 'invokable class',
        ]);

        $this->assertTrue($middleware1->wasCalled);
        $this->assertTrue($middleware2->wasCalled);
        $this->assertFalse($responder->wasCalled);
    }

    public function test_uses_factory_to_build_middleware()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $responder = new InvokableResponder();
        $middleware = new MiddlewareImplementation(true);
        $factory = $this->getMock(InvokableFactory::class);
        $factory->expects($this->once())->method('buildMiddleware')->willReturn($middleware);

        $dispatcher = new DefaultDispatcher(
            ['invokable class' => $responder],
            [MiddlewareImplementation::class],
            'responder',
            'action',
            $factory
        );

        $dispatcher->handle($request, [
            'responder' => 'invokable class',
            'id' => 1,
        ]);

        $this->assertTrue($middleware->wasCalled);
        $this->assertTrue($responder->wasCalled);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_throws_exception_if_no_responder_set()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $dispatcher = new DefaultDispatcher([]);
        $dispatcher->handle($request, []);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_throws_exception_for_unknown_responder()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $dispatcher = new DefaultDispatcher([]);
        $dispatcher->handle($request, ['responder' => 'foobar']);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_throws_exception_for_missing_factory()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $dispatcher = new DefaultDispatcher(['foobar' => 'foobarClass']);
        $dispatcher->handle($request, ['responder' => 'foobar']);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_throws_exception_for_missing_method()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $dispatcher = new DefaultDispatcher(['foobar' => new MethodResponder()]);
        $dispatcher->handle($request, ['responder' => 'foobar', 'action' => 'foobar']);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_throws_exception_for_invalid_responder()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $dispatcher = new DefaultDispatcher(['foobar' => 1]);
        $dispatcher->handle($request, ['responder' => 'foobar']);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_throws_exception_for_invalid_middleware()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $dispatcher = new DefaultDispatcher([], [1]);
        $dispatcher->handle($request, ['responder' => 'foobar']);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_throws_exception_for_unresolvable_parameter()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $responder = function ($id) {

        };

        $dispatcher = new DefaultDispatcher(['foobar' => $responder]);
        $dispatcher->handle($request, ['responder' => 'foobar']);
    }

    public function test_can_add_responder()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $responder = new InvokableResponder();
        $dispatcher = new DefaultDispatcher();
        $dispatcher->addResponder('invokable class', $responder);

        $dispatcher->handle($request, [
            'responder' => 'invokable class',
            'id' => 1,
        ]);

        $this->assertTrue($responder->wasCalled);
    }

    public function test_can_add_middleware()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $responder = new InvokableResponder();
        $middleware = new MiddlewareImplementation(true);

        $dispatcher = new DefaultDispatcher(['invokable class' => $responder]);
        $dispatcher->addMiddleware($middleware);

        $dispatcher->handle($request, ['responder' => 'invokable class', 'id' => 1]);

        $this->assertTrue($middleware->wasCalled);
        $this->assertTrue($responder->wasCalled);
    }

    public function test_can_set_factory()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $responder = new InvokableResponder();
        $factory = $this->getMock(InvokableFactory::class);
        $factory->expects($this->once())->method('buildResponder')->willReturn($responder);

        $dispatcher = new DefaultDispatcher(['invokable class' => InvokableResponder::class]);
        $dispatcher->setFactory($factory);

        $dispatcher->handle($request, ['responder' => 'invokable class', 'id' => 1]);
        $this->assertTrue($responder->wasCalled);
    }

    public function test_can_define_parameters()
    {
        /** @var RequestInterface $request */
        $request = $this->getMock(RequestInterface::class);

        $responder = new MethodResponder();
        $dispatcher = new DefaultDispatcher(['class' => $responder]);
        $dispatcher->defineParameters('r', 'm');

        $dispatcher->handle($request, [
            'r' => 'class',
            'm' => 'action',
            'id' => 1,
        ]);

        $this->assertTrue($responder->wasCalled);
    }
}
