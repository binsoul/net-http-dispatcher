<?php

declare (strict_types = 1);

namespace BinSoul\Net\Http\Dispatcher;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Invokes a responder optionally surrounded by middleware to return a response.
 */
interface Dispatcher
{
    /**
     * @param RequestInterface $request
     * @param mixed[]          $context
     *
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request, array $context);

    /**
     * @param string                           $name
     * @param Responder|object|callable|string $responder
     *
     * @return mixed
     */
    public function addResponder(string $name, $responder);

    /**
     * @param Middleware|callable|string $middleware
     */
    public function addMiddleware($middleware);

    /**
     * @param InvokableFactory $factory
     */
    public function setFactory(InvokableFactory $factory);

    /**
     * @param string $objectParameter
     * @param string $methodParameter
     */
    public function defineParameters(string $objectParameter, string $methodParameter);
}
