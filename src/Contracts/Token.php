<?php

namespace Felix\Sey\Contracts;

use Felix\Sey\Runtime;

/**
 * @internal
 */
abstract class Token
{
    public function __construct(public string $value)
    {
    }

    abstract public function consume(Runtime $runtime): void;
}
