<?php

namespace BinSoul\Net\Http\Dispatcher;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
    public function addResponder($name, $responder);

    /**
     * @param Middleware|callable|string $middleware
     */
    public function addMiddleware($middleware);

    /**
     * @param InvokableFactory $factory
     */
    public function setFactory(InvokableFactory $factory);

    /**
     * @param $objectParameter
     * @param $methodParameter
     */
    public function defineParameters($objectParameter, $methodParameter);
}
