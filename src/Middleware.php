<?php

declare (strict_types = 1);

namespace BinSoul\Net\Http\Dispatcher;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Generates a response for the given request and context or calls the next middleware.
 */
interface Middleware
{
    /**
     * Generates an response.
     *
     * @param RequestInterface $request
     * @param Context          $context
     * @param callable         $next
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, Context $context, callable $next);
}
