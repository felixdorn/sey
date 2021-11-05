<?php

namespace Felix\Sey\Tokens;

use Felix\Sey\Contracts\Token;
use Felix\Sey\Runtime;
use RuntimeException;

class NullToken extends Token
{
    public function __construct()
    {
        $this->value = '';
    }

    public function consume(Runtime $runtime): void
    {
        throw new RuntimeException('Can not consume null token');
    }
}
