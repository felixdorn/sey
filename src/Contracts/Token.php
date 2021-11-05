<?php

namespace Felix\Sey\Contracts;

use Felix\Sey\Runtime;

abstract class Token
{
    public function __construct(public string $value)
    {
    }

    abstract public function consume(Runtime $runtime): void;
}
