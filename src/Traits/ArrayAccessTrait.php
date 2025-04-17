<?php

namespace MicroPHP\Data\Traits;

use ReturnTypeWillChange;

trait ArrayAccessTrait
{
    public function offsetExists($offset): bool
    {
        return isset($this->{$offset});
    }

    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    public function offsetSet($offset, $value): void
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->{$offset});
    }
}
