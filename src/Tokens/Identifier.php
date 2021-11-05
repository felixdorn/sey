<?php

namespace Felix\Sey\Tokens;

use Felix\Sey\Contracts\Token;
use Felix\Sey\Runtime;

/**
 * @internal
 */
class Identifier extends Token
{
    public function consume(Runtime $runtime): void
    {
        $runtime->queue->push($this);
    }
}
