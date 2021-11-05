<?php

namespace Felix\Sey\Exceptions;

use Exception;
use Felix\Sey\Contracts\Token;
use Felix\Sey\Tokens\Func;
use Felix\Sey\Tokens\Identifier;
use Felix\Sey\Tokens\Operator;

class SyntaxError extends Exception
{
    public function __construct(string $message, string|int|float ...$formats)
    {
        parent::__construct(
            sprintf($message, ...$formats)
        );
    }

    public static function unexpectedToken(Token|string $token): SyntaxError
    {
        $value = $token instanceof Token ? $token->value : $token;

        return new self('Unexpected token %s', $value);
    }

    public static function missingParameters(Func|Operator $token): SyntaxError
    {
        return new self('Missing parameters for %s', $token->value);
    }

    public static function incorrectParenthesesNesting(): SyntaxError
    {
        return new self('Incorrect nesting of parentheses');
    }

    public static function undefinedVariable(Identifier $identifier): SyntaxError
    {
        return new self('Undefined variable %s', $identifier->value);
    }

    public static function missingParenthesisOrMisplacedComma(): SyntaxError
    {
        return new self('Missing `(` or misplaced  `,`');
    }
}
