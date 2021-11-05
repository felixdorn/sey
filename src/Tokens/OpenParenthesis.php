<?php

namespace Felix\Sey\Tokens;

use Felix\Sey\Contracts\Token;
use Felix\Sey\Runtime;

class OpenParenthesis extends Token
{
    public function __construct(string $value = '(')
    {
        parent::__construct($value);
    }

    public function consume(Runtime $runtime): void
    {
        $runtime->operatorStack->push($this);
    }
}
