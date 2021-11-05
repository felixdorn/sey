<?php

namespace Felix\Sey\Tokens;

use Felix\Sey\Contracts\Token;
use Felix\Sey\Exceptions\SyntaxError;
use Felix\Sey\Runtime;

/**
 * @internal
 */
class CloseParenthesis extends Token
{
    public function __construct(string $value = ')')
    {
        parent::__construct($value);
    }

    public function consume(Runtime $runtime): void
    {
        $pe = false;

        // Until the token at the top of the stack is a left parenthesis,
        // pop operators off the stack onto the output queue
        while ($token = $runtime->operatorStack->pop()) {
            if ($token instanceof OpenParenthesis) {
                // Pop the left parenthesis from the stack, but not onto the output queue.
                $pe = true;
                break;
            }

            $runtime->queue->push($token);
        }

        // If the stack runs out without finding a left parenthesis, then there are mismatched parentheses.
        if ($pe === false) {
            throw SyntaxError::unexpectedToken(')');
        }

        // If the token at the top of the stack is a function token, pop it onto the output queue.
        if (!$runtime->operatorStack->isEmpty() && $runtime->operatorStack->top() instanceof Func) {
            $runtime->queue->push(
                $runtime->operatorStack->pop()
            );
        }
    }
}
