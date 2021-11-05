<?php

namespace Felix\Sey\Tokens;

use Felix\Sey\Contracts\Token;
use Felix\Sey\Exceptions\SyntaxError;
use Felix\Sey\Runtime;

/**
 * @internal
 */
class Comma extends Token
{
    public function __construct(public string $value = ',')
    {
    }

    public function consume(Runtime $runtime): void
    {
        // If the token is a function argument separator (e.g., a comma):
        $pe = false;

        while (!$runtime->operatorStack->isEmpty()) {
            $token = $runtime->operatorStack->top();

            if ($token instanceof OpenParenthesis) {
                $pe = true;
                break;
            }

            // Until the token at the top of the stack is a left parenthesis,
            // pop operators off the stack onto the output queue.
            $runtime->queue->push(
                $runtime->operatorStack->pop()
            );
        }

        // If no left parentheses are encountered, either the separator was misplaced
        // or parentheses were mismatched.
        if ($pe !== true) {
            throw SyntaxError::missingParenthesisOrMisplacedComma();
        }
    }
}
