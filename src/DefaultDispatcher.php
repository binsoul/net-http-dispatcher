<?php

namespace BinSoul\Net\Http\Dispatcher;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides a default implementation of the {@see Dispatcher} interface.
 */
class DefaultDispatcher implements Dispatcher
{
    /** @var Responder[]|object[]|callable[]|string[] */
    private $responders;
    /** @var Middleware[]|callable[]|string[] */
    private $middlewares;
    /** @var InvokableFactory */
    private $factory;
    /** @var string */
    private $responderParameter;
    /** @var string */
    private $methodParameter;
    /** @var int */
    private $currentMiddlewareID;

    /**
     * Constructs an instance of this class.
     *
     * @param Responder[]|object[]|callable[]|string[] $responders
     * @param Middleware[]|callable[]|string[]         $middlewares
     * @param string                                   $responderParameter
     * @param string                                   $methodParameter
     * @param InvokableFactory                         $factory
     */
    public function __construct(
        array $responders = [],
        array $middlewares = [],
        $responderParameter = 'responder',
        $methodParameter = 'method',
        InvokableFactory $factory = null
    ) {
        $this->responders = $responders;
        $this->middlewares = $middlewares;
        $this->responderParameter = $responderParameter;
        $this->methodParameter = $methodParameter;
        $this->factory = $factory;
    }

    /**
     * Invokes this class.
     *
     * @param RequestInterface $request
     * @param Context          $context
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, Context $context)
    {
        ++$this->currentMiddlewareID;

        if ($this->currentMiddlewareID >= count($this->middlewares)) {
            return $this->invokeResponder($request, $context);
        }

        $middleware = $this->middlewares[$this->currentMiddlewareID];
        if (is_string($middleware)) {
            $middleware = $this->factory->buildMiddleware($middleware);
        }

        if (is_callable($middleware)) {
            return $middleware($request, $context, $this);
        }

        throw new \RuntimeException('Middleware is not callable.');
    }

    public function handle(RequestInterface $request, array $context)
    {
        $this->currentMiddlewareID = -1;

        return $this($request, new DefaultContext($context));
    }

    public function addResponder($name, $responder)
    {
        $this->responders[$name] = $responder;
    }

    public function addMiddleware($middleware)
    {
        $this->middlewares[] = $middleware;
    }

    public function setFactory(InvokableFactory $factory)
    {
        $this->factory = $factory;
    }

    public function defineParameters($objectParameter, $methodParameter)
    {
        $this->responderParameter = $objectParameter;
        $this->methodParameter = $methodParameter;
    }

    /**
     * Invokes a reponder based on the parameters from the given context.
     *
     * @param RequestInterface $request
     * @param Context          $context
     *
     * @return ResponseInterface
     */
    private function invokeResponder(RequestInterface $request, Context $context)
    {
        if (!$context->hasParameter($this->responderParameter)) {
            throw new \InvalidArgumentException('Responder parameter is not set in the provided context.');
        }

        $responderName = $context->getParameter($this->responderParameter);
        if (!isset($this->responders[$responderName])) {
            throw new \RuntimeException(sprintf('Responder "%s" is unknown.', $responderName));
        }

        $responder = $this->responders[$responderName];
        if (is_string($responder)) {
            if ($this->factory === null) {
                throw new \RuntimeException(sprintf('No factory provided to build responder "%s".', $responder));
            }

            $responder = $this->factory->buildResponder($responder);
        }

        if ($responder instanceof \Closure) {
            $function = new \ReflectionFunction($responder);

            return $function->invokeArgs(
                $this->buildParameters($function->getParameters(), $request, $context)
            );
        } elseif (is_object($responder)) {
            $methodName = '__invoke';
            if ($context->hasParameter($this->methodParameter)) {
                $methodName = $context->getParameter($this->methodParameter);
            }

            $reflectionClass = new \ReflectionClass($responder);
            if (!$reflectionClass->hasMethod($methodName)) {
                throw new \RuntimeException(sprintf('Responder has no method "%s".', $methodName));
            }

            $method = $reflectionClass->getMethod($methodName);
            $method->setAccessible(true);

            return $method->invokeArgs(
                $responder,
                $this->buildParameters($method->getParameters(), $request, $context)
            );
        }

        throw new \RuntimeException('Responder is not callable.');
    }

    /**
     * Builds an array of resolved parameters suitable for invokeArgs.
     *
     * @param \ReflectionParameter[] $parameters
     * @param Context                $context
     *
     * @return mixed[]
     */
    private function buildParameters(array $parameters, RequestInterface $request, Context $context)
    {
        $result = [];
        foreach ($parameters as $index => $parameter) {
            if ($context->hasParameter($parameter->getName())) {
                $result[] = $context->getParameter($parameter->getName());

                continue;
            }

            $dependency = $parameter->getClass();

            if ($dependency === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $result[] = $parameter->getDefaultValue();

                    continue;
                }
            } elseif ($dependency->getName() == RequestInterface::class ||
                in_array(RequestInterface::class, $dependency->getInterfaceNames())
            ) {
                $result[] = $request;

                continue;
            } elseif ($dependency->getName() == Context::class ||
                in_array(Context::class, $dependency->getInterfaceNames())
            ) {
                $result[] = $context;

                continue;
            } elseif ($parameter->isOptional()) {
                $result[] = $parameter->getDefaultValue();

                continue;
            }

            throw new \RuntimeException(sprintf('Unable to resolve dependency "%s".', $parameter->getName()));
        }

        return $result;
    }
}
