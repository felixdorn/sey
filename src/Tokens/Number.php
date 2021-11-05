<?php

namespace Felix\Sey\Tokens;

use Felix\Sey\Contracts\Token;
use Felix\Sey\Runtime;

/**
 * @internal
 */
class Number extends Token
{
    public function consume(Runtime $runtime): void
    {
        $runtime->queue->push($this);
    }
}
