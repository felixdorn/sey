<?php

namespace Felix\Sey\Contracts;

use Felix\Sey\Runtime;
use Felix\Sey\Tokens\NullToken;

abstract class Token
{
    public function __construct(public string $value)
    {
    }

    public static function empty(): NullToken
    {
        return new NullToken();
    }

    abstract public function consume(Runtime $runtime): void;
}
