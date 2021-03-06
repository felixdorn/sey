<?php

namespace Felix\Sey;

use Felix\Sey\Tokens\CloseParenthesis;
use Felix\Sey\Tokens\Comma;
use Felix\Sey\Tokens\Func;
use Felix\Sey\Tokens\Identifier;
use Felix\Sey\Tokens\Number;
use Felix\Sey\Tokens\OpenParenthesis;
use Felix\Sey\Tokens\Operator;

/**
 * @internal
 */
class Lexer
{
    public static function lex(string $code): Stack
    {
        $tokens = new Stack();

        preg_match_all('/[,+\-*\/^%()]|\d*\.\d+|\d+\.\d*|\d+|[a-z_A-Z!]+[a-zA-Z0-9_]*|[ \t]+/', $code, $matches);

        $matches = array_filter($matches[0], fn ($match) => trim($match) !== '');

        foreach ($matches as $k => $match) {
            $behind = $matches[$k - 1] ?? '';
            $ahead  = $matches[$k + 1] ?? '';
            $value  = trim($match);

            if (is_numeric($value)) {
                if ($behind === ')') {
                    $tokens->push(new Operator('*'));
                }

                $tokens->push(new Number($value));
                continue;
            }

            if ($value === ',') {
                $tokens->push(new Comma());
            } elseif ($value === ')') {
                $tokens->push(new CloseParenthesis());
            } elseif ($value === '(') {
                if ($behind === ')' || is_numeric($behind)) {
                    $tokens->push(new Operator('*'));
                }

                $tokens->push(new OpenParenthesis());
            } elseif (Operator::isValid($value)) {
                $tokens->push(new Operator($value));
            } elseif ($ahead === '(') {
                $tokens->push(new Func($value));
            } else {
                $tokens->push(new Identifier($value));
            }
        }

        return $tokens->rewind();
    }
}
