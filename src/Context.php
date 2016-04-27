<?php

declare (strict_types = 1);

namespace BinSoul\Net\Http\Dispatcher;

/**
 * Provides access to runtime parameters.
 */
interface Context
{
    /**
     * Indicates if a parameter exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasParameter(string $name): bool;

    /**
     * Returns the value of a parameter or the given default if the parameters doesn't exist.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getParameter(string $name, $default = null);

    /**
     * Returns an instance with the new parameter.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return Context
     */
    public function withParameter(string $name, $value): Context;
}
