<?php

declare(strict_types=1);

namespace MicroPHP\Data\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class DataAttribute
{
    public function __construct(
        /** toArray()时转下划线 */
        public bool $toSnakeArray = false,
    ) {}
}
