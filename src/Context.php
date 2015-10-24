<?php

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
    public function hasParameter($name);

    /**
     * Returns the value of a parameter or the given default if the parameters doesn't exist.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getParameter($name, $default = null);

    /**
     * Returns an instance with the new parameter.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function withParameter($name, $value);
}
