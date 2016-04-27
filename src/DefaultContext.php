<?php

declare (strict_types = 1);

namespace BinSoul\Net\Http\Dispatcher;

use BinSoul\Common\DataObject;

/**
 * Provides a default implementation of the {@see Context} interface.
 */
class DefaultContext implements Context
{
    use DataObject;

    public function hasParameter(string $name): bool
    {
        return $this->hasData($name);
    }

    public function getParameter(string $name, $default = null)
    {
        if (!$this->hasData($name)) {
            return $default;
        }

        return $this->__get($name);
    }

    public function withParameter(string $name, $value): Context
    {
        $data = $this->getData();
        $data[$name] = $value;

        return new static($data);
    }
}
