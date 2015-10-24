<?php

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
    public function buildResponder($name);

    /**
     * Builds the middleware identified by the provided name.
     *
     * @param string $name
     *
     * @return Middleware|callable
     */
    public function buildMiddleware($name);
}
