<?php

namespace Felix\BcExpr\Exceptions;

use Exception;
use Felix\BcExpr\Token;

class SyntaxError extends Exception
{
    public static function unexpectedToken(Token|string $token): SyntaxError
    {
        $value = $token instanceof Token ? $token->value : $token;

        return self::new('Unexpected token %s', $value);
    }

    public static function new(string $message, float|int|string ...$formats): SyntaxError
    {
        return new self(sprintf($message, ...$formats));
    }

    public static function missingParameters(Token $token): SyntaxError
    {
        return self::new('Missing parameters for %s', $token->value);
    }
}
