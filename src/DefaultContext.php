<?php

namespace BinSoul\Net\Http\Dispatcher;

use BinSoul\Common\DataObject;

class DefaultContext implements Context
{
    use DataObject;

    public function hasParameter($name)
    {
        return $this->hasData($name);
    }

    public function getParameter($name, $default = null)
    {
        if (!$this->hasData($name)) {
            return $default;
        }

        return $this->__get($name);
    }

    public function withParameter($name, $value)
    {
        $data = $this->getData();
        $data[$name] = $value;

        return new self($data);
    }
}
