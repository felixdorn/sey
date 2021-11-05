<?php

namespace Felix\Sey\Contracts;

use Felix\Sey\Runtime;
use Felix\Sey\Tokens\NullToken;

abstract class Token
{
    public function __construct(public string $value)
    {
    }

    abstract public function consume(Runtime $runtime): void;
}
