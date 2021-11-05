<?php

namespace Felix\Sey;

class Token
{
    public const T_NUMBER            = 1;
    public const T_FUNCTION          = 2;
    public const T_OPEN_PARENTHESIS  = 4;
    public const T_CLOSE_PARENTHESIS = 8;
    public const T_IDENTIFIER        = 16;
    public const T_COMMA             = 32;
    public const T_PLUS              = 64;
    public const T_MINUS             = 128;
    public const T_TIMES             = 256;
    public const T_DIV               = 512;
    public const T_POW               = 1024;
    public const T_MOD               = 2048;

    public int $type;
    public string $value;
    public int $argc = 0;

    public function __construct(int $type, string $value)
    {
        $this->type  = $type;
        $this->value = $value;
    }

    public function is(int ...$comparisons): bool
    {
        foreach ($comparisons as $comparison) {
            if ($comparison === $this->type) {
                return true;
            }
        }

        return false;
    }
}
