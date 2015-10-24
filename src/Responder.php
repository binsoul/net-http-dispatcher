<?php

namespace BinSoul\Net\Http\Dispatcher;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Generates a response for the given request and context.
 */
interface Responder
{
    /**
     * * Generates an response.
     *
     * @param RequestInterface $request
     * @param Context          $context
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, Context $context);
}
