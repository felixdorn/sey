<?php

namespace Felix\Sey\Tokens;

use Felix\Sey\Contracts\Token;
use Felix\Sey\Runtime;

/**
 * @internal
 */
class Func extends Token
{
    public int $argc = 0;

    public function consume(Runtime $runtime): void
    {
        // If the token is a function token, then push it onto the stack.
        $runtime->operatorStack->push($this);
        $argc        = 0;
        $parenthesis = 0;

        // Skipping the `(`
        $runtime->tokens->next()->current()->consume($runtime);

        // more tokens?
        while ($runtime->tokens->ahead()) {
            $ahead = $runtime->tokens->next()->current();
            $ahead->consume($runtime);

            // nested parenthesis inside function calls
            if ($ahead instanceof OpenParenthesis) {
                $parenthesis++;
            } elseif ($ahead instanceof CloseParenthesis && $parenthesis-- === 0) {
                break;
            }

            $argc = max($argc, 1); // at least 1 arg if bracket not closed immediately

            if ($ahead instanceof Comma) {
                $argc++;
            }
        }

        $this->argc = $argc;
    }
}
