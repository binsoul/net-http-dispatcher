<?php

declare (strict_types = 1);

namespace BinSoul\Net\Http\Dispatcher;

/**
 * Builds responders and middlewares.
 */
interface InvokableFactory
{
    /**
     * Builds the responder identified by the provided name.
     *
     * @param string $name
     *
     * @return Responder|object|callable
     */
    public function buildResponder(string $name);

    /**
     * Builds the middleware identified by the provided name.
     *
     * @param string $name
     *
     * @return Middleware|callable
     */
    public function buildMiddleware(string $name);
}
